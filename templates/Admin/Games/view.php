<?php $this->assign('title', 'Game Details'); ?>
<div class="col-md-12" style="min-height: 500px;">
    <!-- Top Action Buttons: Edit Game, Edit Opponent, Back -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="<?= $this->Url->build(['action' => 'edit', $game->id]) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Game
                </a>
                <?php if (!empty($game->opponent_id)) : ?>
                    <a href="<?= $this->Url->build([
                        'controller' => 'Opponents', 'action' => 'edit', $game->opponent_id,
                    ]) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Opponent
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
            </div>
        </div>
    </div>

    <!-- Game Header -->

    <div class="row">
        <span class="h3 col-xl-12 text-center text-nowrap text-break">
            <?= h($game->team_season->team->team_description ?? '') ?>
            <?= h($game->team_season->season->start ?? '') ?>-<?= h($game->team_season->season->end ?? '') ?>
        </span>
    </div>
    <div class="row">
        <span class="h3 col-xl-6 text-center text-nowrap text-break">
            <?= h($game->team_season->team->team_nickname ?? '') ?>
        </span>
        <span class="h3 col-xl-1 text-center text-nowrap">
            <?php
            // Display 'Vs' for home (1) and neutral (3), '@' for road (2)
            $vsText = $game->hrn == 2 ? '@' : 'Vs';
            echo h($vsText);
            ?>
        </span>
        <span class="h3 col-xl-5 text-center text-nowrap">
            <?= h($game->opponent->opponent_name ?? '') ?>
        </span>
        <span class="h3 col-sm-12 text-center text-nowrap">
            <?= h((new \DateTime($game->game_date))->format('l, F jS, Y')) ?>
        </span>
        <hr>
    </div>

    <!-- Game Information and Scores -->
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <table class="table text-center">
                    <thead>
                        <tr>
                            <th class="text-center h2"><?= h($game->team_season->team->team_nickname ?? 'Team') ?></th>
                            <th></th>
                            <th class="text-center h2"><?= h($game->opponent->opponent_name ?? 'Opponent') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php
                            $murPts = $game->pts_mur ?? 0;
                            $oppPts = $game->pts_opp ?? 0;
                            $murBold = $murPts > $oppPts ? 'bold' : 'normal';
                            $oppBold = $oppPts > $murPts ? 'bold' : 'normal';
                            ?>
                            <td style="font-size: 40px; font-weight: <?= $murBold ?>;">
                                <?= h($game->pts_mur ?? '') ?>
                            </td>
                            <td></td>
                            <td style="font-size: 40px; font-weight: <?= $oppBold ?>;">
                                <?= h($game->pts_opp ?? '') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4">
                <table class="table table-condensed table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <?php
                            // Determine number of periods from eav template or fallback
                            $periods = 2;
                            $otPeriods = 0;
                            foreach ($eav as $k => $v) {
                                if (preg_match('/^period_(\d+)_team$/', $k, $m)) {
                                    $periods = max($periods, (int)$m[1]);
                                }
                                if (preg_match('/^overtime_(\d+)_team$/', $k, $m)) {
                                    $otPeriods = max($otPeriods, (int)$m[1]);
                                }
                            }
                            for ($i = 1; $i <= $periods; $i++) {
                                echo '<th>' . $i . '</th>';
                            }
                            for ($i = 1; $i <= $otPeriods; $i++) {
                                echo '<th>OT' . ($otPeriods > 1 ? $i : '') . '</th>';
                            }
                            ?>
                            <th>F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= h($game->team_season->team->team_scorebug ?? '') ?></td>
                            <?php for ($i = 1; $i <= $periods; $i++) : ?>
                                <td><?= h($eav['period_' . $i . '_team'] ?? '') ?></td>
                            <?php endfor; ?>
                            <?php for ($i = 1; $i <= $otPeriods; $i++) : ?>
                                <td><?= h($eav['overtime_' . $i . '_team'] ?? '') ?></td>
                            <?php endfor; ?>
                            <td style="font-weight: <?= $murBold ?>;"><?= h($game->pts_mur ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><?= h($game->opponent->opponent_abbr ?? 'Opponent') ?></td>
                            <?php for ($i = 1; $i <= $periods; $i++) : ?>
                                <td><?= h($eav['period_' . $i . '_opponent'] ?? '') ?></td>
                            <?php endfor; ?>
                            <?php for ($i = 1; $i <= $otPeriods; $i++) : ?>
                                <td><?= h($eav['overtime_' . $i . '_opponent'] ?? '') ?></td>
                            <?php endfor; ?>
                            <td style="font-weight: <?= $oppBold ?>;"><?= h($game->pts_opp ?? '') ?></td>
                        </tr>
                    </tbody>
                </table>
                <span class=""><?= h($game->game_type->game_type_name ?? '') ?></span>
            </div>
        </div>
        <hr>

        <!-- Game Details (Site, Attendance, Officials, Notes, Game Time) -->
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-lg-9 h4">
                        <?php
                        $placeName = $game->site->place->place_name ?? '';
                        $placeState = $game->site->place->place_state ?? '';
                        $siteName = $game->site->site_name ?? '';
                        $locationText = trim($placeName . ($placeState ? ', ' . $placeState : ''));
                        if ($siteName) {
                            $locationText .= ($locationText ? ' ' : '') . $siteName;
                        }
                        ?>
                        <?= h($locationText) ?>
                        <?php if (!empty($game->site_id)) : ?>
                            <a href="<?= $this->Url->build([
                                'controller' => 'Sites', 'action' => 'edit', $game->site_id,
                            ]) ?>" class="btn btn-sm btn-outline-primary" title="Edit Site">
                                <i class="bi bi-pencil"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($game->attendance)) : ?>
                    <div class="col-lg-3">
                        Attendance: <?= h($game->attendance ?? '') ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    Referees: <?= h($eav['official_1'] ?? '') ?>
                    <?= !empty($eav['official_2']) ? ', ' . h($eav['official_2']) : '' ?>
                    <?= !empty($eav['official_3']) ? ', ' . h($eav['official_3']) : '' ?>
                </div>
                <div>
                    <span><?= h($game->notes ?? '') ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <?php if (!empty($game->game_time)) : ?>
                <div>
                    <?php
                    // Format game_time to 12-hour format if it exists
                    $gameTimeDisplay = '';
                    if (!empty($game->game_time)) {
                        if ($game->game_time instanceof \DateTimeInterface) {
                            $gameTimeDisplay = $game->game_time->format('g:i A');
                        } else {
                            // Try to parse string time
                            try {
                                $timeObj = new \DateTime($game->game_time);
                                $gameTimeDisplay = $timeObj->format('g:i A');
                            } catch (\Exception $e) {
                                $gameTimeDisplay = $game->game_time;
                            }
                        }
                    }
                    ?>
                    <span>Game Time: <?= h($gameTimeDisplay) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($game->game_duration)) : ?>
                    <div>
                        <span>Duration: <?= h($game->game_duration) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <hr>

        <!-- Basketball Game Statistics -->
        <?php if (isset($hasSportConfig) && $hasSportConfig && $game->team_season->team->sport_id === 1) : ?>
            <?= $this->element('Admin/basketball_game_stats', [
                'game' => $game,
                'teamBoxStats' => $teamBoxStats ?? [],
                'opponentBoxStats' => $opponentBoxStats ?? [],
                'teamPeriodStats' => $teamPeriodStats ?? [],
                'opponentPeriodStats' => $opponentPeriodStats ?? [],
                'playerStats' => $playerStats ?? null,
                'opponentPlayerStats' => $opponentPlayerStats ?? null,
                'teamTeamStats' => $teamTeamStats ?? null,
                'opponentTeamStats' => $opponentTeamStats ?? null,
                'hasPeriodStats' => $hasPeriodStats ?? false,
            ]) ?>
        <?php endif; ?>

        <!-- Legacy Box Score Display (for reference/fallback) -->
        <?php if (false && (!empty($teamBoxStats) || !empty($opponentBoxStats))) : ?>
            <!-- Team Player Stats -->
            <div class="row mt-4">
                <div class="col-12">
                    <h3>
                        <?= h($game->team_season->team->team_nickname ?? 'Murray State') ?>
                        - <?= h($game->pts_mur ?? '') ?>
                    </h3>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <table id="team-player-stats"
                           class="table table-sm table-bordered table-striped"
                           style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Pos</th>
                                <th>Min</th>
                                <th>FGM</th>
                                <th>FGA</th>
                                <th>3PM</th>
                                <th>3PA</th>
                                <th>FTM</th>
                                <th>FTA</th>
                                <th>OREB</th>
                                <th>DREB</th>
                                <th>REB</th>
                                <th>AST</th>
                                <th>STL</th>
                                <th>BLK</th>
                                <th>TO</th>
                                <th>PF</th>
                                <th>PTS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($playerStats) && $playerStats->count() > 0) : ?>
                                <?php foreach ($playerStats as $stat) : ?>
                                    <tr>
                                        <td><?= h($stat->team_season_roster->roster_number ?? '') ?></td>
                                        <td>
                                            <?= h(
                                                $stat->team_season_roster->person->display ??
                                                $stat->team_season_roster->person->full ?? ''
                                            ) ?>
                                        </td>
                                        <td>
                                            <?= $stat->GS ?
                                                h($stat->team_season_roster->roster_position ?? '') : '' ?>
                                        </td>
                                        <td><?= h($stat->MIN ?? '') ?></td>
                                        <td><?= h($stat->FGM ?? '') ?></td>
                                        <td><?= h($stat->FGA ?? '') ?></td>
                                        <td><?= h($stat->TPM ?? '') ?></td>
                                        <td><?= h($stat->TPA ?? '') ?></td>
                                        <td><?= h($stat->FTM ?? '') ?></td>
                                        <td><?= h($stat->FTA ?? '') ?></td>
                                        <td><?= h($stat->ORB ?? '') ?></td>
                                        <td><?= h($stat->DRB ?? '') ?></td>
                                        <td><?= h($stat->RB ?? '') ?></td>
                                        <td><?= h($stat->AST ?? '') ?></td>
                                        <td><?= h($stat->STL ?? '') ?></td>
                                        <td><?= h($stat->BS ?? '') ?></td>
                                        <td><?= h($stat->TRN ?? '') ?></td>
                                        <td><?= h($stat->PF ?? '') ?></td>
                                        <td><?= h($stat->PTS ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="19" class="text-center text-muted">
                                        <em>No player statistics entered yet</em>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($teamBoxStats)) : ?>
                            <?php if (!empty($teamTeamStats)) : ?>
                                <tbody class="table-light">
                                    <tr>
                                        <td colspan="3"><strong>TEAM</strong></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td><?= h($teamTeamStats->ORB ?? '0') ?></td>
                                        <td><?= h($teamTeamStats->DRB ?? '0') ?></td>
                                        <td><?= h($teamTeamStats->RB ?? '0') ?></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td><?= h($teamTeamStats->TRN ?? '0') ?></td>
                                        <td>-</td>
                                        <td><?= h($teamTeamStats->PTS ?? '0') ?></td>
                                    </tr>
                                </tbody>
                            <?php endif; ?>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="3"><strong>TOTALS</strong></td>
                                    <td>-</td>
                                    <td><?= h($teamBoxStats['FGM'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['FGA'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['TPM'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['TPA'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['FTM'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['FTA'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['ORB'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['DRB'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['RB'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['AST'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['STL'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['BS'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['TRN'] ?? '0') ?></td>
                                    <td><?= h($teamBoxStats['PF'] ?? '0') ?></td>
                                    <td><strong><?= h($game->pts_mur ?? '0') ?></strong></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Team Technical Fouls -->
            <div class="row mb-3">
                <div class="col-12">
                    <strong>Technical Fouls:</strong>
                    <?php
                    $hasTechFouls = isset($teamBoxStats['TF']) && $teamBoxStats['TF'] !== null
                        && $teamBoxStats['TF'] !== '' && $teamBoxStats['TF'] !== '0';
                    if ($hasTechFouls) {
                        echo h($teamBoxStats['TF']);
                    } else {
                        echo 'NONE';
                    }
                    ?>
                </div>
            </div>

            <!-- Team Shooting Stats by Period -->
            <?php if (!empty($teamPeriodStats)) : ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <table class="table table-bordered table-sm text-center">
                            <thead class="table-light">
                                <tr>
                                    <th width="1%">Scoring</th>
                                    <th>FG</th>
                                    <th>3PT</th>
                                    <th>FT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Iterate through all periods dynamically
                                foreach ($teamPeriodStats as $periodKey => $periodData) :
                                    // Determine period label
                                    if (strpos($periodKey, 'OT') === 0) {
                                        $otNumber = substr($periodKey, 2);
                                        $periodLabel = $otNumber ? "OT $otNumber" : 'Overtime';
                                    } else {
                                        // Use simple ordinal numbers for regular periods
                                        $ordinals = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'];
                                        $periodLabel = $ordinals[$periodKey] ?? $periodKey . 'th';
                                    }

                                    $fga = (int)($periodData['FGA'] ?? 0);
                                    $fgm = (int)($periodData['FGM'] ?? 0);
                                    $fgPct = $fga > 0 ? number_format($fgm / $fga * 100, 1) . '%' : '';

                                    $tpa = (int)($periodData['TPA'] ?? 0);
                                    $tpm = (int)($periodData['TPM'] ?? 0);
                                    $tpPct = $tpa > 0 ? number_format($tpm / $tpa * 100, 1) . '%' : '';

                                    $fta = (int)($periodData['FTA'] ?? 0);
                                    $ftm = (int)($periodData['FTM'] ?? 0);
                                    $ftPct = $fta > 0 ? number_format($ftm / $fta * 100, 1) . '%' : '';
                                    ?>
                                    <tr>
                                        <td class="text-start"><strong><?= h($periodLabel) ?></strong></td>
                                        <td>
                                            <?= h($fgm) ?>-<?= h($fga) ?>
                                            <?php if ($fgPct) : ?>
                                                <br><small class="text-muted"><?= h($fgPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= h($tpm) ?>-<?= h($tpa) ?>
                                            <?php if ($tpPct) : ?>
                                                <br><small class="text-muted"><?= h($tpPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= h($ftm) ?>-<?= h($fta) ?>
                                            <?php if ($ftPct) : ?>
                                                <br><small class="text-muted"><?= h($ftPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!empty($teamBoxStats)) :
                                    $totalFGA = (int)($teamBoxStats['FGA'] ?? 0);
                                    $totalFGM = (int)($teamBoxStats['FGM'] ?? 0);
                                    $totalFGPct = $totalFGA > 0
                                        ? number_format($totalFGM / $totalFGA * 100, 1) . '%' : '';

                                    $totalTPA = (int)($teamBoxStats['TPA'] ?? 0);
                                    $totalTPM = (int)($teamBoxStats['TPM'] ?? 0);
                                    $totalTPPct = $totalTPA > 0
                                        ? number_format($totalTPM / $totalTPA * 100, 1) . '%' : '';

                                    $totalFTA = (int)($teamBoxStats['FTA'] ?? 0);
                                    $totalFTM = (int)($teamBoxStats['FTM'] ?? 0);
                                    $totalFTPct = $totalFTA > 0
                                        ? number_format($totalFTM / $totalFTA * 100, 1) . '%' : '';
                                    ?>
                                    <tr class="table-secondary">
                                        <td class="text-start"><strong>Total</strong></td>
                                        <td>
                                            <?= h($totalFGM) ?>-<?= h($totalFGA) ?>
                                            <?php if ($totalFGPct) : ?>
                                                <br><small class="text-muted"><?= h($totalFGPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= h($totalTPM) ?>-<?= h($totalTPA) ?>
                                            <?php if ($totalTPPct) : ?>
                                                <br><small class="text-muted"><?= h($totalTPPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= h($totalFTM) ?>-<?= h($totalFTA) ?>
                                            <?php if ($totalFTPct) : ?>
                                                <br><small class="text-muted"><?= h($totalFTPct) ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Opponent Player Stats -->
            <?php if (!empty($opponentBoxStats)) : ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h3><?= h($game->opponent->opponent_name ?? 'Opponent') ?> - <?= h($game->pts_opp ?? '') ?></h3>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <table id="opponent-player-stats"
                               class="table table-sm table-bordered table-striped"
                               style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Player</th>
                                    <th>Pos</th>
                                    <th>Min</th>
                                    <th>FGM</th>
                                    <th>FGA</th>
                                    <th>3PM</th>
                                    <th>3PA</th>
                                    <th>FTM</th>
                                    <th>FTA</th>
                                    <th>OREB</th>
                                    <th>DREB</th>
                                    <th>REB</th>
                                    <th>AST</th>
                                    <th>STL</th>
                                    <th>BLK</th>
                                    <th>TO</th>
                                    <th>PF</th>
                                    <th>PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($opponentPlayerStats) && $opponentPlayerStats->count() > 0) : ?>
                                    <?php foreach ($opponentPlayerStats as $stat) : ?>
                                        <tr>
                                            <td><?= h($stat->jersey ?? '') ?></td>
                                            <td><?= h($stat->name ?? '') ?></td>
                                            <td><?= h($stat->position ?? '') ?></td>
                                            <td><?= h($stat->MIN ?? '') ?></td>
                                            <td><?= h($stat->FGM ?? '') ?></td>
                                            <td><?= h($stat->FGA ?? '') ?></td>
                                            <td><?= h($stat->TPM ?? '') ?></td>
                                            <td><?= h($stat->TPA ?? '') ?></td>
                                            <td><?= h($stat->FTM ?? '') ?></td>
                                            <td><?= h($stat->FTA ?? '') ?></td>
                                            <td><?= h($stat->ORB ?? '') ?></td>
                                            <td><?= h($stat->DRB ?? '') ?></td>
                                            <td><?= h($stat->RB ?? '') ?></td>
                                            <td><?= h($stat->AST ?? '') ?></td>
                                            <td><?= h($stat->STL ?? '') ?></td>
                                            <td><?= h($stat->BS ?? '') ?></td>
                                            <td><?= h($stat->TRN ?? '') ?></td>
                                            <td><?= h($stat->PF ?? '') ?></td>
                                            <td><?= h($stat->PTS ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="19" class="text-center text-muted">
                                            <em>No opponent player statistics entered yet</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($opponentBoxStats)) : ?>
                                <?php if (!empty($opponentTeamStats)) : ?>
                                    <tbody class="table-light">
                                        <tr>
                                            <td colspan="3"><strong>TEAM</strong></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td><?= h($opponentTeamStats->ORB ?? '0') ?></td>
                                            <td><?= h($opponentTeamStats->DRB ?? '0') ?></td>
                                            <td><?= h($opponentTeamStats->RB ?? '0') ?></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td><?= h($opponentTeamStats->TRN ?? '0') ?></td>
                                            <td>-</td>
                                            <td><?= h($opponentTeamStats->PTS ?? '0') ?></td>
                                        </tr>
                                    </tbody>
                                <?php endif; ?>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="3"><strong>TOTALS</strong></td>
                                        <td>-</td>
                                        <td><?= h($opponentBoxStats['FGM'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['FGA'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['TPM'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['TPA'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['FTM'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['FTA'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['ORB'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['DRB'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['RB'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['AST'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['STL'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['BS'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['TRN'] ?? '0') ?></td>
                                        <td><?= h($opponentBoxStats['PF'] ?? '0') ?></td>
                                        <td><strong><?= h($game->pts_opp ?? '0') ?></strong></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Opponent Technical Fouls -->
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Technical Fouls:</strong>
                        <?php
                        $hasOppTechFouls = isset($opponentBoxStats['TF'])
                            && $opponentBoxStats['TF'] !== null && $opponentBoxStats['TF'] !== ''
                            && $opponentBoxStats['TF'] !== '0';
                        if ($hasOppTechFouls) {
                            echo h($opponentBoxStats['TF']);
                        } else {
                            echo 'NONE';
                        }
                        ?>
                    </div>
                </div>

                <!-- Opponent Shooting Stats by Period -->
                <?php if (!empty($opponentPeriodStats)) : ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <table class="table table-bordered table-sm text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th width="1%">Scoring</th>
                                        <th>FG</th>
                                        <th>3PT</th>
                                        <th>FT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Iterate through all periods dynamically
                                    foreach ($opponentPeriodStats as $periodKey => $periodData) :
                                        // Determine period label
                                        if (strpos($periodKey, 'OT') === 0) {
                                            $otNumber = substr($periodKey, 2);
                                            $periodLabel = $otNumber ? "OT $otNumber" : 'Overtime';
                                        } else {
                                            // Use simple ordinal numbers for regular periods
                                            $ordinals = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'];
                                            $periodLabel = $ordinals[$periodKey] ?? $periodKey . 'th';
                                        }

                                        $fga = (int)($periodData['FGA'] ?? 0);
                                        $fgm = (int)($periodData['FGM'] ?? 0);
                                        $fgPct = $fga > 0 ? number_format($fgm / $fga * 100, 1) . '%' : '';

                                        $tpa = (int)($periodData['TPA'] ?? 0);
                                        $tpm = (int)($periodData['TPM'] ?? 0);
                                        $tpPct = $tpa > 0 ? number_format($tpm / $tpa * 100, 1) . '%' : '';

                                        $fta = (int)($periodData['FTA'] ?? 0);
                                        $ftm = (int)($periodData['FTM'] ?? 0);
                                        $ftPct = $fta > 0 ? number_format($ftm / $fta * 100, 1) . '%' : '';
                                        ?>
                                        <tr>
                                            <td class="text-start"><strong><?= h($periodLabel) ?></strong></td>
                                            <td>
                                                <?= h($fgm) ?>-<?= h($fga) ?>
                                                <?php if ($fgPct) : ?>
                                                    <br><small class="text-muted"><?= h($fgPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($tpm) ?>-<?= h($tpa) ?>
                                                <?php if ($tpPct) : ?>
                                                    <br><small class="text-muted"><?= h($tpPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($ftm) ?>-<?= h($fta) ?>
                                                <?php if ($ftPct) : ?>
                                                    <br><small class="text-muted"><?= h($ftPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (!empty($opponentBoxStats)) :
                                        $totalFGA = (int)($opponentBoxStats['FGA'] ?? 0);
                                        $totalFGM = (int)($opponentBoxStats['FGM'] ?? 0);
                                        $totalFGPct = $totalFGA > 0
                                            ? number_format($totalFGM / $totalFGA * 100, 1) . '%' : '';

                                        $totalTPA = (int)($opponentBoxStats['TPA'] ?? 0);
                                        $totalTPM = (int)($opponentBoxStats['TPM'] ?? 0);
                                        $totalTPPct = $totalTPA > 0
                                            ? number_format($totalTPM / $totalTPA * 100, 1) . '%' : '';

                                        $totalFTA = (int)($opponentBoxStats['FTA'] ?? 0);
                                        $totalFTM = (int)($opponentBoxStats['FTM'] ?? 0);
                                        $totalFTPct = $totalFTA > 0
                                            ? number_format($totalFTM / $totalFTA * 100, 1) . '%' : '';
                                        ?>
                                        <tr class="table-secondary">
                                            <td class="text-start"><strong>Total</strong></td>
                                            <td>
                                                <?= h($totalFGM) ?>-<?= h($totalFGA) ?>
                                                <?php if ($totalFGPct) : ?>
                                                    <br><small class="text-muted"><?= h($totalFGPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($totalTPM) ?>-<?= h($totalTPA) ?>
                                                <?php if ($totalTPPct) : ?>
                                                    <br><small class="text-muted"><?= h($totalTPPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($totalFTM) ?>-<?= h($totalFTA) ?>
                                                <?php if ($totalFTPct) : ?>
                                                    <br><small class="text-muted"><?= h($totalFTPct) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Comparative Stats Table -->
            <?php if (!empty($teamBoxStats) && !empty($opponentBoxStats)) : ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h4 class="text-center">Team Comparison</h4>
                        <table class="table table-bordered table-sm text-center">
                            <thead class="table-light">
                                <tr>
                                    <th><?= h($game->team_season->team->team_nickname ?? 'Murray State') ?></th>
                                    <th>Category</th>
                                    <th><?= h($game->opponent->opponent_name ?? 'Opponent') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= h($teamBoxStats['PNT'] ?? '0') ?></td>
                                    <th>Points in Paint</th>
                                    <td><?= h($opponentBoxStats['PNT'] ?? '0') ?></td>
                                </tr>
                                <tr>
                                    <td><?= h($teamBoxStats['OTO'] ?? '0') ?></td>
                                    <th>Points off Turnovers</th>
                                    <td><?= h($opponentBoxStats['OTO'] ?? '0') ?></td>
                                </tr>
                                <tr>
                                    <td><?= h($teamBoxStats['SND'] ?? '0') ?></td>
                                    <th>2nd Chance Points</th>
                                    <td><?= h($opponentBoxStats['SND'] ?? '0') ?></td>
                                </tr>
                                <tr>
                                    <td><?= h($teamBoxStats['FB'] ?? '0') ?></td>
                                    <th>Fast Break Points</th>
                                    <td><?= h($opponentBoxStats['FB'] ?? '0') ?></td>
                                </tr>
                                <tr>
                                    <td><?= h($teamBoxStats['BN'] ?? '0') ?></td>
                                    <th>Bench Points</th>
                                    <td><?= h($opponentBoxStats['BN'] ?? '0') ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <strong>Times Tied:</strong> <?= h($teamBoxStats['TIED'] ?? '0') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <strong>Lead Changes:</strong> <?= h($teamBoxStats['LC'] ?? '0') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <hr>
        <?php endif; ?>

        <div class="row"><!-- Murray Stats --></div>
        <div class="row"><!-- Opponent Stats --></div>
        <hr>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>
</div>
