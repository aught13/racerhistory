<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SportConfigService;
use Cake\Http\Response;

/**
 * StatBasketGameBox Controller (Admin)
 *
 * Manages basketball game box scores and period statistics.
 * Updates season totals for team and opponent when box scores are saved (final period only).
 *
 * @property \App\Model\Table\StatBasketGameBoxTable $StatBasketGameBox
 */
class StatBasketGameBoxController extends AppController
{
    /**
     * SportConfigService instance
     *
     * @var \App\Service\SportConfigService
     */
    protected SportConfigService $sportConfigService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->sportConfigService = new SportConfigService();
    }

    /**
     * Edit/Create game box scores (final period only - period Z)
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void
     */
    public function gameBox(int $gameId): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        // Check if this is a basketball game
        if ($game->team_season && $game->team_season->team && $game->team_season->team->sport) {
            $sportName = strtolower($game->team_season->team->sport->sport_name);
            if ($sportName !== 'basketball') {
                $this->Flash->error(__('Game box scores are currently only supported for basketball games.'));

                return $this->redirect(['controller' => 'Games', 'action' => 'edit', $gameId]);
            }
        }

        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');

        // Load existing box scores (final period only)
        $teamBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'])
            ->first();

        $opponentId = $game->opponent_id ?? 0;
        $opponentBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'])
            ->first();

        // Check if we have period stats already
        $periodStats = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->order(['period' => 'ASC'])
            ->all()
            ->toArray();

        $hasPeriodStats = !empty($periodStats);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Store original stats for comparison
            $originalTeamBox = $teamBox ? clone $teamBox : null;
            $originalOpponentBox = $opponentBox ? clone $opponentBox : null;

            // Save team final stats (period Z, opponent_id 0)
            if (!empty($data['team'])) {
                $teamData = $data['team'];
                $teamData['game_id'] = $gameId;
                $teamData['opponent_id'] = 0;
                $teamData['period'] = 'Z';

                if ($teamBox) {
                    $teamBox = $boxTable->patchEntity($teamBox, $teamData);
                } else {
                    $teamBox = $boxTable->newEntity($teamData);
                }

                if (!$boxTable->save($teamBox)) {
                    $this->Flash->error(__('Could not save team box scores. Please try again.'));

                    return null;
                }
            }

            // Save opponent final stats (period Z, with opponent_id)
            if (!empty($data['opponent'])) {
                $oppData = $data['opponent'];
                $oppData['game_id'] = $gameId;
                $oppData['opponent_id'] = $opponentId;
                $oppData['period'] = 'Z';

                if ($opponentBox) {
                    $opponentBox = $boxTable->patchEntity($opponentBox, $oppData);
                } else {
                    $opponentBox = $boxTable->newEntity($oppData);
                }

                if (!$boxTable->save($opponentBox)) {
                    $this->Flash->error(__('Could not save opponent box scores. Please try again.'));

                    return null;
                }
            }

            // Update season totals if checkbox is selected (final period only)
            $addToTotals = $this->request->getData('add_to_totals');
            if ($addToTotals && $teamBox && $opponentBox) {
                $this->updateSeasonTotals($game, $teamBox, $opponentBox, $originalTeamBox, $originalOpponentBox);
            }

            $this->Flash->success(__('Game box scores have been saved.'));

            // If user wants to add period stats, redirect to period entry
            if (!empty($data['add_periods'])) {
                return $this->redirect(['action' => 'gameBoxPeriods', $gameId]);
            }

            return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
        }

        // Get stat field labels from SportConfigService
        $sportId = $game->team_season->team->sport->id;
        $fieldLabels = $this->sportConfigService->getAllFieldLabels($sportId);

        $this->set(compact('game', 'teamBox', 'opponentBox', 'fieldLabels', 'hasPeriodStats'));

        return null;
    }

    /**
     * Edit period-by-period box scores
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function gameBoxPeriods(int $gameId): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');

        // Get number of periods from game
        $numPeriods = (int)($game->periods ?? 2);
        $numOT = (int)($game->ot ?? 0);
        $opponentId = $game->opponent_id ?? 0;

        // Load existing period stats
        $existingStats = [];
        $periodStats = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->order(['period' => 'ASC'])
            ->all();

        foreach ($periodStats as $stat) {
            $key = ($stat->opponent_id == 0 ? 'team' : 'opponent') . '_' . $stat->period;
            $existingStats[$key] = $stat;
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $saveErrors = [];

            // Process each period
            for ($p = 1; $p <= $numPeriods; $p++) {
                // Team period stats
                if (!empty($data['team_' . $p])) {
                    $teamData = $data['team_' . $p];
                    $teamData['game_id'] = $gameId;
                    $teamData['opponent_id'] = 0;
                    $teamData['period'] = (string)$p;

                    $existingKey = 'team_' . $p;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $teamData);
                    } else {
                        $entity = $boxTable->newEntity($teamData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Team Period $p";
                    }
                }

                // Opponent period stats
                if (!empty($data['opponent_' . $p])) {
                    $oppData = $data['opponent_' . $p];
                    $oppData['game_id'] = $gameId;
                    $oppData['opponent_id'] = $opponentId;
                    $oppData['period'] = (string)$p;

                    $existingKey = 'opponent_' . $p;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $oppData);
                    } else {
                        $entity = $boxTable->newEntity($oppData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Opponent Period $p";
                    }
                }
            }

            // Process overtime periods
            for ($ot = 1; $ot <= $numOT; $ot++) {
                $otPeriod = 'OT' . ($ot > 1 ? $ot : '');

                // Team OT stats
                if (!empty($data['team_' . $otPeriod])) {
                    $teamData = $data['team_' . $otPeriod];
                    $teamData['game_id'] = $gameId;
                    $teamData['opponent_id'] = 0;
                    $teamData['period'] = $otPeriod;

                    $existingKey = 'team_' . $otPeriod;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $teamData);
                    } else {
                        $entity = $boxTable->newEntity($teamData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Team $otPeriod";
                    }
                }

                // Opponent OT stats
                if (!empty($data['opponent_' . $otPeriod])) {
                    $oppData = $data['opponent_' . $otPeriod];
                    $oppData['game_id'] = $gameId;
                    $oppData['opponent_id'] = $opponentId;
                    $oppData['period'] = $otPeriod;

                    $existingKey = 'opponent_' . $otPeriod;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $oppData);
                    } else {
                        $entity = $boxTable->newEntity($oppData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Opponent $otPeriod";
                    }
                }
            }

            if (empty($saveErrors)) {
                $this->Flash->success(__('Period box scores have been saved.'));

                return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
            }

            $this->Flash->error(__('Could not save some period stats: {0}', implode(', ', $saveErrors)));
        }

        // Get stat field labels
        $sportId = $game->team_season->team->sport->id;
        $fieldLabels = $this->sportConfigService->getAllFieldLabels($sportId);

        $this->set(compact('game', 'numPeriods', 'numOT', 'existingStats', 'fieldLabels'));

        return null;
    }

    /**
     * Update season totals for team and opponent based on box score (final period only)
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @param \App\Model\Entity\StatBasketGameBox $teamBox Team box score (period Z)
     * @param \App\Model\Entity\StatBasketGameBox $opponentBox Opponent box score (period Z)
     * @param \App\Model\Entity\StatBasketGameBox|null $originalTeamBox Original team box (for updates)
     * @param \App\Model\Entity\StatBasketGameBox|null $originalOpponentBox Original opponent box (for updates)
     * @return void
     */
    private function updateSeasonTotals(
        \App\Model\Entity\Game $game,
        \App\Model\Entity\StatBasketGameBox $teamBox,
        \App\Model\Entity\StatBasketGameBox $opponentBox,
        ?\App\Model\Entity\StatBasketGameBox $originalTeamBox = null,
        ?\App\Model\Entity\StatBasketGameBox $originalOpponentBox = null
    ): void {
        if (!$game->team_season_id) {
            return;
        }

        $teamSeasonTable = $this->fetchTable('StatBasketSeasonTeam');
        $opponentSeasonTable = $this->fetchTable('StatBasketSeasonOpponent');

        // Fields that should be summed for season totals
        $sumFields = [
            'GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS',
            'TF', 'FD', 'BD', 'EB', 'PP', 'FB', 'BN', 'TIED', 'LC',
        ];

        // Update team season totals
        $teamSeasonStat = $teamSeasonTable->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$teamSeasonStat) {
            $teamSeasonStat = $teamSeasonTable->newEmptyEntity();
            $teamSeasonStat->team_season_id = $game->team_season_id;
        }

        // If this is an edit, subtract the original values first
        if ($originalTeamBox) {
            foreach ($sumFields as $field) {
                if ($teamSeasonStat->get($field) && $originalTeamBox->get($field)) {
                    $current = (int)$teamSeasonStat->get($field);
                    $original = (int)$originalTeamBox->get($field);
                    $teamSeasonStat->set($field, max(0, $current - $original));
                }
            }
        }

        // Add the new values
        foreach ($sumFields as $field) {
            if ($teamBox->get($field)) {
                $current = (int)($teamSeasonStat->get($field) ?? 0);
                $new = (int)$teamBox->get($field);
                $teamSeasonStat->set($field, $current + $new);
            }
        }

        $teamSeasonTable->save($teamSeasonStat);

        // Update opponent season totals
        $opponentId = $game->opponent_id;
        $opponentSeasonStat = $opponentSeasonTable->find()
            ->where(['opponent_id' => $opponentId])
            ->first();

        if (!$opponentSeasonStat) {
            $opponentSeasonStat = $opponentSeasonTable->newEmptyEntity();
            $opponentSeasonStat->opponent_id = $opponentId;
        }

        // If this is an edit, subtract the original values first
        if ($originalOpponentBox) {
            foreach ($sumFields as $field) {
                if ($opponentSeasonStat->get($field) && $originalOpponentBox->get($field)) {
                    $current = (int)$opponentSeasonStat->get($field);
                    $original = (int)$originalOpponentBox->get($field);
                    $opponentSeasonStat->set($field, max(0, $current - $original));
                }
            }
        }

        // Add the new values
        foreach ($sumFields as $field) {
            if ($opponentBox->get($field)) {
                $current = (int)($opponentSeasonStat->get($field) ?? 0);
                $new = (int)$opponentBox->get($field);
                $opponentSeasonStat->set($field, $current + $new);
            }
        }

        $opponentSeasonTable->save($opponentSeasonStat);
    }
}
