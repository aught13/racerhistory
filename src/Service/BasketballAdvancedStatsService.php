<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use InvalidArgumentException;

/**
 * Calculates college basketball advanced statistics from box-score totals.
 */
class BasketballAdvancedStatsService
{
    private const FREE_THROW_POSSESSION_MULTIPLIER = 0.475;
    private const POINTS_PRODUCED_FREE_THROW_MULTIPLIER = 0.4;
    private const REGULATION_MINUTES = 40.0;

    /**
     * Calculate advanced statistics for one player.
     *
     * Rate values are returned as ratios so presentation layers can format them
     * consistently. Stored team minutes are divided by five to get Team MP/5.
     *
     * @param object|array<string, mixed> $player Player totals.
     * @param object|array<string, mixed> $team Team totals.
     * @param object|array<string, mixed> $opponent Opponent totals.
     * @param float|null $teamMinutesPerPlayer Team MP divided by five.
     * @return array<string,float|null>
     */
    public function calculatePlayerStats(
        array|object $player,
        array|object $team,
        array|object $opponent,
        ?float $teamMinutesPerPlayer,
    ): array {
        $fgm = $this->number($player, 'FGM');
        $fga = $this->number($player, 'FGA');
        $tpm = $this->number($player, 'TPM');
        $tpa = $this->number($player, 'TPA');
        $ftm = $this->number($player, 'FTM');
        $fta = $this->number($player, 'FTA');
        $pts = $this->number($player, 'PTS');
        $minutes = $this->number($player, 'MIN');
        $turnovers = $this->turnovers($player);
        $teamMinutes = $teamMinutesPerPlayer !== null && $teamMinutesPerPlayer > 0
            ? $teamMinutesPerPlayer
            : null;

        $teamFgm = $this->number($team, 'FGM');
        $teamFga = $this->number($team, 'FGA');
        $teamFta = $this->number($team, 'FTA');
        $teamTurnovers = $this->turnovers($team);
        $opponentFga = $this->number($opponent, 'FGA');
        $opponentTpa = $this->number($opponent, 'TPA');
        $possessions = $this->estimatePossessions($team, $opponent);
        $teamScoringPossessions = $this->scoringPossessions(
            $team,
            self::POINTS_PRODUCED_FREE_THROW_MULTIPLIER,
        );
        $teamPlayPercent = $this->teamPlayPercent($team, $teamScoringPossessions);
        $teamOrbWeight = $this->teamOrbWeight($team, $opponent, $teamPlayPercent);
        $teamMinutesFactor = $teamMinutes !== null && $minutes !== null
            ? $minutes / $teamMinutes
            : null;
        $assistDenominator = $teamMinutesFactor !== null && $teamFgm !== null && $fgm !== null
            ? ($teamMinutesFactor * $teamFgm) - $fgm
            : null;
        $teamUsedPlays = $teamFga !== null && $teamFta !== null && $teamTurnovers !== null
            ? $teamFga + (self::FREE_THROW_POSSESSION_MULTIPLIER * $teamFta) + $teamTurnovers
            : null;
        $playerUsedPlays = $fga !== null && $fta !== null && $turnovers !== null
            ? $fga + (self::FREE_THROW_POSSESSION_MULTIPLIER * $fta) + $turnovers
            : null;

        return [
            'PPG' => $this->divide($pts, $this->number($player, 'GP')),
            '2PM' => $fgm !== null && $tpm !== null ? $fgm - $tpm : null,
            '2PA' => $fga !== null && $tpa !== null ? $fga - $tpa : null,
            'FG%' => $this->divide($fgm, $fga),
            '2P%' => $this->divide(
                $fgm !== null && $tpm !== null ? $fgm - $tpm : null,
                $fga !== null && $tpa !== null ? $fga - $tpa : null,
            ),
            '3P%' => $this->divide($tpm, $tpa),
            'FT%' => $this->freeThrowRate($ftm, $fta),
            'FT/FGA' => $this->divide($ftm, $fga),
            'TS%' => $this->divide(
                $pts,
                $fga !== null && $fta !== null
                    ? 2 * ($fga + (self::FREE_THROW_POSSESSION_MULTIPLIER * $fta))
                    : null,
            ),
            'eFG%' => $this->divide(
                $fgm !== null && $tpm !== null ? $fgm + (0.5 * $tpm) : null,
                $fga,
            ),
            '3PAr' => $this->divide($tpa, $fga),
            'FTr' => $this->divide($fta, $fga),
            'ORB%' => $this->reboundPercentage(
                $this->number($player, 'ORB'),
                $minutes,
                $teamMinutes,
                $this->number($team, 'ORB'),
                $this->defensiveRebounds($opponent),
            ),
            'DRB%' => $this->reboundPercentage(
                $this->defensiveRebounds($player),
                $minutes,
                $teamMinutes,
                $this->defensiveRebounds($team),
                $this->number($opponent, 'ORB'),
            ),
            'TRB%' => $this->reboundPercentage(
                $this->totalRebounds($player),
                $minutes,
                $teamMinutes,
                $this->totalRebounds($team),
                $this->totalRebounds($opponent),
            ),
            'AST%' => $this->divide($this->number($player, 'AST'), $assistDenominator),
            'STL%' => $this->divide(
                $this->multiply($this->number($player, 'STL'), $teamMinutes),
                $this->multiply($minutes, $possessions),
            ),
            'BLK%' => $this->divide(
                $this->multiply($this->number($player, 'BS'), $teamMinutes),
                $this->multiply(
                    $minutes,
                    $opponentFga !== null && $opponentTpa !== null ? $opponentFga - $opponentTpa : null,
                ),
            ),
            'TOV%' => $this->divide($turnovers, $playerUsedPlays),
            'USG%' => $this->divide(
                $this->multiply($playerUsedPlays, $teamMinutes),
                $this->multiply($minutes, $teamUsedPlays),
            ),
            'PProd' => $this->pointsProduced(
                $player,
                $team,
                $teamMinutes,
                $teamScoringPossessions,
                $teamPlayPercent,
                $teamOrbWeight,
            ),
        ];
    }

    /**
     * Attach advanced values to the player entities used by public stats views.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $players Player stat entities.
     * @param object|array<string, mixed> $team Team totals.
     * @param object|array<string, mixed> $opponent Opponent totals.
     * @return void
     */
    public function enrichPlayers(iterable $players, array|object $team, array|object $opponent): void
    {
        $rows = [];
        foreach ($players as $player) {
            if (!$player instanceof EntityInterface) {
                throw new InvalidArgumentException('Player statistics must be Cake entities.');
            }
            $rows[] = $player;
        }

        $teamMinutesPerPlayer = $this->resolveTeamMinutesPerPlayer($team, $rows);
        foreach ($rows as $player) {
            $player->set(
                'advanced_stats',
                $this->calculatePlayerStats($player, $team, $opponent, $teamMinutesPerPlayer),
            );
        }
    }

    /**
     * Return the shooting and per-game metrics suitable for a team totals row.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param object|array<string, mixed> $opponent Opponent totals.
     * @return array<string,float|null>
     */
    public function calculateTeamSummary(array|object $team, array|object $opponent): array
    {
        $teamMinutesPerPlayer = $this->resolveTeamMinutesPerPlayer($team, []);
        $teamPlayerStats = [];
        $summaryPlayerFields = [
            'GP', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
            'AST', 'STL', 'BS', 'TRN', 'PTS',
        ];
        foreach ($summaryPlayerFields as $key) {
            $teamPlayerStats[$key] = $this->number($team, $key);
        }
        $teamPlayerStats['MIN'] = $teamMinutesPerPlayer;

        $stats = $this->calculatePlayerStats(
            $teamPlayerStats,
            $team,
            $opponent,
            $teamMinutesPerPlayer,
        );
        $summaryKeys = [
            'PPG', '2PM', '2PA', 'FG%', '2P%', '3P%', 'FT%', 'FT/FGA', 'TS%', 'eFG%', '3PAr', 'FTr',
            'ORB%', 'DRB%', 'TRB%', 'AST%', 'STL%', 'BLK%', 'TOV%', 'USG%',
        ];

        return array_intersect_key($stats, array_flip($summaryKeys));
    }

    /**
     * Resolve Team MP / 5 from stored team minutes or the available player minutes.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param array<int,\Cake\Datasource\EntityInterface> $players Player totals.
     * @return float
     */
    private function resolveTeamMinutesPerPlayer(array|object $team, array $players): float
    {
        $teamMinutes = $this->number($team, 'MIN');
        if ($teamMinutes !== null && $teamMinutes > 0) {
            return $teamMinutes / 5;
        }

        $teamGames = $this->number($team, 'GP');
        $playerGames = null;
        $playerMinutes = 0.0;
        $hasPlayerMinutes = false;
        foreach ($players as $player) {
            $games = $this->number($player, 'GP');
            if ($games !== null) {
                $playerGames = max($playerGames ?? 0.0, $games);
            }

            $minutes = $this->number($player, 'MIN');
            if ($minutes !== null && $minutes > 0) {
                $playerMinutes += $minutes;
                $hasPlayerMinutes = true;
            }
        }

        $games = $teamGames !== null && $teamGames > 0 ? $teamGames : $playerGames;
        $minimumMinutes = self::REGULATION_MINUTES * ($games !== null && $games > 0 ? $games : 1);
        $observedMinutes = $hasPlayerMinutes ? $playerMinutes / 5 : $minimumMinutes;

        return max($minimumMinutes, $observedMinutes);
    }

    /**
     * Calculate Dean Oliver's points-produced estimate using its specified possession factors.
     *
     * @param object|array<string, mixed> $player Player totals.
     * @param object|array<string, mixed> $team Team totals.
     * @param float|null $teamMinutesPerPlayer Team MP divided by five.
     * @param float|null $teamScoringPossessions Team scoring possessions.
     * @param float|null $teamPlayPercent Team play percentage.
     * @param float|null $teamOrbWeight Team offensive-rebound weight.
     * @return float|null
     */
    private function pointsProduced(
        array|object $player,
        array|object $team,
        ?float $teamMinutesPerPlayer,
        ?float $teamScoringPossessions,
        ?float $teamPlayPercent,
        ?float $teamOrbWeight,
    ): ?float {
        $fgm = $this->number($player, 'FGM');
        $fga = $this->number($player, 'FGA');
        $tpm = $this->number($player, 'TPM');
        $ftm = $this->number($player, 'FTM');
        $fta = $this->number($player, 'FTA');
        $pts = $this->number($player, 'PTS');
        $orb = $this->number($player, 'ORB');
        $ast = $this->number($player, 'AST');
        $minutes = $this->number($player, 'MIN');
        $teamFgm = $this->number($team, 'FGM');
        $teamFga = $this->number($team, 'FGA');
        $teamTpm = $this->number($team, 'TPM');
        $teamFtm = $this->number($team, 'FTM');
        $teamFta = $this->number($team, 'FTA');
        $teamPts = $this->number($team, 'PTS');
        $teamOrb = $this->number($team, 'ORB');
        $qAst = $this->qAst($player, $team, $teamMinutesPerPlayer);
        $orbScoringPossessions = $this->scoringPossessions($team, self::FREE_THROW_POSSESSION_MULTIPLIER);

        if (
            $fgm === null || $fga === null || $tpm === null || $ftm === null || $fta === null
            || $pts === null || $orb === null || $ast === null || $teamFgm === null
            || $teamFga === null || $teamTpm === null || $teamFtm === null || $teamFta === null
            || $teamPts === null || $teamOrb === null || $teamScoringPossessions === null
            || $teamScoringPossessions <= 0 || $teamPlayPercent === null || $teamOrbWeight === null
            || $qAst === null
        ) {
            return null;
        }

        $fieldGoalPointsRatio = $this->divide($pts - $ftm, 2 * $fga);
        if ($fieldGoalPointsRatio === null) {
            return null;
        }
        $fieldGoalPart = 2 * ($fgm + (0.5 * $tpm)) * (1 - (0.5 * $fieldGoalPointsRatio * $qAst));

        $assistPart = 0.0;
        if ($ast !== 0.0) {
            $teammateFgm = $teamFgm - $fgm;
            $assistFgmRatio = $this->divide(
                $teammateFgm + (0.5 * ($teamTpm - $tpm)),
                $teammateFgm,
            );
            $assistPointsRatio = $this->divide(
                $teamPts - $teamFtm - ($pts - $ftm),
                2 * ($teamFga - $fga),
            );
            if ($assistFgmRatio === null || $assistPointsRatio === null) {
                return null;
            }
            $assistPart = 2 * $assistFgmRatio * 0.5 * $assistPointsRatio * $ast;
        }

        $teamOrbShare = $this->divide($teamOrb, $teamScoringPossessions);
        $teamPointsPerOrbScoringPossession = $this->divide($teamPts, $orbScoringPossessions);
        if (
            $teamOrbShare === null || $teamPointsPerOrbScoringPossession === null
            || $teamMinutesPerPlayer === null || $minutes === null
        ) {
            return null;
        }

        $orbPart = $orb * $teamOrbWeight * $teamPlayPercent * $teamPointsPerOrbScoringPossession;
        $scoringPossessionAdjustment = 1 - ($teamOrbShare * $teamOrbWeight * $teamPlayPercent);

        return (($fieldGoalPart + $assistPart + $ftm) * $scoringPossessionAdjustment) + $orbPart;
    }

    /**
     * Estimate the proportion of the player's field goals that were assisted.
     *
     * @param object|array<string, mixed> $player Player totals.
     * @param object|array<string, mixed> $team Team totals.
     * @param float|null $teamMinutesPerPlayer Team MP divided by five.
     * @return float|null
     */
    private function qAst(array|object $player, array|object $team, ?float $teamMinutesPerPlayer): ?float
    {
        $minutes = $this->number($player, 'MIN');
        $playerFgm = $this->number($player, 'FGM');
        $playerAst = $this->number($player, 'AST');
        $teamFgm = $this->number($team, 'FGM');
        $teamAst = $this->number($team, 'AST');
        if (
            $minutes === null || $playerFgm === null || $playerAst === null || $teamFgm === null
            || $teamFgm <= 0 || $teamAst === null || $teamMinutesPerPlayer === null
            || $teamMinutesPerPlayer <= 0
        ) {
            return null;
        }

        $teamMinutes = $teamMinutesPerPlayer * 5;
        $minuteShare = $minutes / $teamMinutesPerPlayer;
        $firstPart = $minuteShare * 1.14 * ($teamAst - $playerAst) / $teamFgm;
        $assistedFieldGoals = ($teamFgm / $teamMinutes * $minutes * 5) - $playerFgm;
        $assists = ($teamAst / $teamMinutes * $minutes * 5) - $playerAst;
        $secondPart = $this->divide($assists, $assistedFieldGoals);
        if ($secondPart === null) {
            return null;
        }

        return $firstPart + ($secondPart * (1 - $minuteShare));
    }

    /**
     * Calculate team scoring possessions using the supplied FT multiplier.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param float $freeThrowMultiplier Free-throw possession multiplier.
     * @return float|null
     */
    private function scoringPossessions(array|object $team, float $freeThrowMultiplier): ?float
    {
        $fgm = $this->number($team, 'FGM');
        $ftm = $this->number($team, 'FTM');
        $fta = $this->number($team, 'FTA');
        $ftPercent = $this->freeThrowRate($ftm, $fta);
        if ($fgm === null || $fta === null || $ftPercent === null) {
            return null;
        }

        return $fgm
            + ((1 - ((1 - $ftPercent) ** 2)) * $freeThrowMultiplier * $fta);
    }

    /**
     * Calculate the percentage of team plays that become scoring possessions.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param float|null $teamScoringPossessions Team scoring possessions.
     * @return float|null
     */
    private function teamPlayPercent(array|object $team, ?float $teamScoringPossessions): ?float
    {
        $teamFga = $this->number($team, 'FGA');
        $teamFta = $this->number($team, 'FTA');
        $teamTurnovers = $this->turnovers($team);
        $teamPlays = $teamFga !== null && $teamFta !== null && $teamTurnovers !== null
            ? $teamFga + (self::POINTS_PRODUCED_FREE_THROW_MULTIPLIER * $teamFta) + $teamTurnovers
            : null;

        return $this->divide($teamScoringPossessions, $teamPlays);
    }

    /**
     * Calculate the team's offensive-rebound weight.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param object|array<string, mixed> $opponent Opponent totals.
     * @param float|null $teamPlayPercent Team play percentage.
     * @return float|null
     */
    private function teamOrbWeight(
        array|object $team,
        array|object $opponent,
        ?float $teamPlayPercent,
    ): ?float {
        $teamOrb = $this->number($team, 'ORB');
        $opponentDrb = $this->defensiveRebounds($opponent);
        $teamOrbPercent = $this->divide(
            $teamOrb,
            $teamOrb !== null && $opponentDrb !== null ? $teamOrb + $opponentDrb : null,
        );
        if ($teamOrbPercent === null || $teamPlayPercent === null) {
            return null;
        }

        $unreboundedPlayShare = (1 - $teamOrbPercent) * $teamPlayPercent;
        $orbWeightDenominator = $unreboundedPlayShare
            + ($teamOrbPercent * (1 - $teamPlayPercent));

        return $this->divide($unreboundedPlayShare, $orbWeightDenominator);
    }

    /**
     * Estimate possessions for both teams using the Sports Reference college formula.
     *
     * @param object|array<string, mixed> $team Team totals.
     * @param object|array<string, mixed> $opponent Opponent totals.
     * @return float|null
     */
    private function estimatePossessions(array|object $team, array|object $opponent): ?float
    {
        $teamPlays = $this->estimatedPlays($team);
        $opponentPlays = $this->estimatedPlays($opponent);
        if ($teamPlays === null || $opponentPlays === null) {
            return null;
        }

        return 0.5 * ($teamPlays + $opponentPlays);
    }

    /**
     * Calculate estimated plays for one side of the game.
     *
     * @param object|array<string, mixed> $stats Team or opponent totals.
     * @return float|null
     */
    private function estimatedPlays(array|object $stats): ?float
    {
        $fga = $this->number($stats, 'FGA');
        $fta = $this->number($stats, 'FTA');
        $orb = $this->number($stats, 'ORB');
        $turnovers = $this->turnovers($stats);
        if ($fga === null || $fta === null || $orb === null || $turnovers === null) {
            return null;
        }

        return $fga + (self::FREE_THROW_POSSESSION_MULTIPLIER * $fta) - $orb + $turnovers;
    }

    /**
     * Calculate a player's share of available rebounds.
     *
     * @param float|null $playerRebounds Player rebounds of the selected type.
     * @param float|null $playerMinutes Player minutes.
     * @param float|null $teamMinutesPerPlayer Team MP divided by five.
     * @param float|null $teamRebounds Team rebounds of the selected type.
     * @param float|null $opponentRebounds Opponent rebounds of the complementary type.
     * @return float|null
     */
    private function reboundPercentage(
        ?float $playerRebounds,
        ?float $playerMinutes,
        ?float $teamMinutesPerPlayer,
        ?float $teamRebounds,
        ?float $opponentRebounds,
    ): ?float {
        $availableRebounds = $teamRebounds !== null && $opponentRebounds !== null
            ? $teamRebounds + $opponentRebounds
            : null;

        return $this->divide(
            $this->multiply($playerRebounds, $teamMinutesPerPlayer),
            $this->multiply($playerMinutes, $availableRebounds),
        );
    }

    /**
     * Read a numeric stat from an entity, array, or plain object.
     *
     * @param object|array<string, mixed> $stats Stat source.
     * @param string $key Stat field.
     * @return float|null
     */
    private function number(array|object $stats, string $key): ?float
    {
        if (is_array($stats)) {
            $value = $stats[$key] ?? null;
        } elseif ($stats instanceof EntityInterface) {
            $value = $stats->get($key);
        } else {
            $value = $stats->{$key} ?? null;
        }

        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Return turnovers from the application's TRN field or a standard TOV field.
     *
     * @param object|array<string, mixed> $stats Stat source.
     * @return float|null
     */
    private function turnovers(array|object $stats): ?float
    {
        return $this->number($stats, 'TRN') ?? $this->number($stats, 'TOV');
    }

    /**
     * Return defensive rebounds directly or derive them from total rebounds.
     *
     * @param object|array<string, mixed> $stats Stat source.
     * @return float|null
     */
    private function defensiveRebounds(array|object $stats): ?float
    {
        $drb = $this->number($stats, 'DRB');
        if ($drb !== null) {
            return $drb;
        }

        $totalRebounds = $this->totalRebounds($stats);
        $orb = $this->number($stats, 'ORB');
        if ($totalRebounds === null || $orb === null || $totalRebounds < $orb) {
            return null;
        }

        return $totalRebounds - $orb;
    }

    /**
     * Return total rebounds directly or derive them from offensive and defensive rebounds.
     *
     * @param object|array<string, mixed> $stats Stat source.
     * @return float|null
     */
    private function totalRebounds(array|object $stats): ?float
    {
        $totalRebounds = $this->number($stats, 'RB');
        if ($totalRebounds !== null) {
            return $totalRebounds;
        }

        $orb = $this->number($stats, 'ORB');
        $drb = $this->number($stats, 'DRB');
        if ($orb === null || $drb === null) {
            return null;
        }

        return $orb + $drb;
    }

    /**
     * Calculate made free throws per attempt, treating an observed zero-attempt line as 0%.
     *
     * @param float|null $made Free throws made.
     * @param float|null $attempted Free throws attempted.
     * @return float|null
     */
    private function freeThrowRate(?float $made, ?float $attempted): ?float
    {
        if ($made === null || $attempted === null) {
            return null;
        }
        if ($attempted === 0.0) {
            return $made === 0.0 ? 0.0 : null;
        }

        return $this->divide($made, $attempted);
    }

    /**
     * Multiply only when both operands are available.
     *
     * @param float|null $left Left operand.
     * @param float|null $right Right operand.
     * @return float|null
     */
    private function multiply(?float $left, ?float $right): ?float
    {
        return $left !== null && $right !== null ? $left * $right : null;
    }

    /**
     * Divide by a positive denominator and reject non-finite results.
     *
     * @param float|null $numerator Numerator.
     * @param float|null $denominator Denominator.
     * @return float|null
     */
    private function divide(?float $numerator, ?float $denominator): ?float
    {
        if ($numerator === null || $denominator === null || $denominator <= 0) {
            return null;
        }

        $result = $numerator / $denominator;

        return is_finite($result) ? $result : null;
    }
}
