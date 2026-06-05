<?php
/**
 * Basketball Game Stats Element
 *
 * Displays basketball game statistics with:
 * - Player stats tables for both team and opponent
 * - Team totals footer rows
 * - Shooting percentages by period
 * - Team comparison table
 * - DataTable with sorting
 * - Dynamic column filtering (hides empty columns)
 * - Add/Edit buttons
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Game $game
 * @var array $teamBoxStats Team final period Z stats
 * @var array $opponentBoxStats Opponent final period Z stats
 * @var array $teamPeriodStats Team stats by period
 * @var array $opponentPeriodStats Opponent stats by period
 * @var \Cake\Collection\CollectionInterface $playerStats Team player stats
 * @var \Cake\Collection\CollectionInterface $opponentPlayerStats Opponent player stats
 * @var \App\Model\Entity\StatBasketGameTeam|null $teamTeamStats Team dead ball stats
 * @var \App\Model\Entity\StatBasketGameTeam|null $opponentTeamStats Opponent dead ball stats
 * @var bool $hasPeriodStats Whether period breakdown stats exist
 */

// Define all possible stat columns for player tables
$allColumns = [
    'MIN' => 'Min',
    'FGM' => 'FGM',
    'FGA' => 'FGA',
    'TPM' => '3PM',
    'TPA' => '3PA',
    'FTM' => 'FTM',
    'FTA' => 'FTA',
    'ORB' => 'OREB',
    'DRB' => 'DREB',
    'RB' => 'REB',
    'AST' => 'AST',
    'STL' => 'STL',
    'BS' => 'BLK',
    'TRN' => 'TO',
    'PF' => 'PF',
    'PTS' => 'PTS',
];

// Determine which columns have data across team player stats
$teamColumnsWithData = [];
foreach ($allColumns as $key => $label) {
    $hasData = false;

    // Check team player stats
    if ($playerStats) {
        foreach ($playerStats as $stat) {
            if (!empty($stat->$key)) {
                $hasData = true;
                break;
            }
        }
    }

    // Check team box stats totals
    if (!$hasData && !empty($teamBoxStats[$key])) {
        $hasData = true;
    }

    // Check team team stats (dead ball rebounds, turnovers)
    if (!$hasData && $teamTeamStats && !empty($teamTeamStats->$key)) {
        $hasData = true;
    }

    if ($hasData) {
        $teamColumnsWithData[$key] = $label;
    }
}

// Determine which columns have data across opponent player stats
$opponentColumnsWithData = [];
foreach ($allColumns as $key => $label) {
    $hasData = false;

    // Check opponent player stats
    if ($opponentPlayerStats) {
        foreach ($opponentPlayerStats as $stat) {
            if (!empty($stat->$key)) {
                $hasData = true;
                break;
            }
        }
    }

    // Check opponent box stats totals
    if (!$hasData && !empty($opponentBoxStats[$key])) {
        $hasData = true;
    }

    // Check opponent team stats
    if (!$hasData && $opponentTeamStats && !empty($opponentTeamStats->$key)) {
        $hasData = true;
    }

    if ($hasData) {
        $opponentColumnsWithData[$key] = $label;
    }
}

$hasTeamStats = $playerStats && $playerStats->count() > 0;
$hasOpponentStats = $opponentPlayerStats && $opponentPlayerStats->count() > 0;
$hasAnyStats = !empty($teamBoxStats) || !empty($opponentBoxStats) || $hasTeamStats || $hasOpponentStats;
$hasBoxStats = !empty($teamBoxStats) || !empty($opponentBoxStats);
$hasTeamTeamStats = $teamTeamStats !== null || $opponentTeamStats !== null;
?>

<!-- Basketball Statistics Controls -->
<div class="row mt-4 mb-3">
    <div class="col-12">
        <h3 class="mb-3">Game Statistics</h3>
        <div class="btn-group" role="group">
            <!-- 1. Game Box Score -->
            <a href="<?= $this->Url->build(['controller' => 'StatBasketGameBox', 'action' => 'gameBox', $game->id]) ?>" class="btn btn-sm <?= $hasBoxStats ? 'btn-success' : 'btn-outline-success' ?>">
                <i class="bi bi-clipboard-data"></i> <?= $hasBoxStats ? 'Edit' : 'Add' ?> Box Score
            </a>

            <!-- 2. Game Box Periods (if period stats exist) -->
            <?php if ($hasPeriodStats) : ?>
                <a href="<?= $this->Url->build([
                    'controller' => 'StatBasketGameBox',
                    'action' => 'gameBoxPeriods', $game->id,
                ]) ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-clock-history"></i> Edit Period Stats
                </a>
            <?php endif; ?>

            <!-- 3. View Player Stats (if any rows are set) -->
            <?php if ($hasTeamStats) : ?>
                <a href="<?= $this->Url->build([
                    'controller' => 'StatBasketGamePerson',
                    'action' => 'view',
                    $game->id,
                ]) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-person"></i> View Player Stats
                </a>
            <?php endif; ?>

            <!-- 4. Add Player Stats -->
            <a href="<?= $this->Url->build([
                'controller' => 'StatBasketGamePerson',
                'action' => 'add',
                $game->id,
            ]) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Add Player
            </a>

            <!-- 5. View Opponent Individual Stats (if any rows are set) -->
            <?php if ($hasOpponentStats) : ?>
                <a href="<?= $this->Url->build([
                    'controller' => 'StatBasketGameOpponent',
                    'action' => 'view',
                    $game->id,
                ]) ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-people"></i> View Opp. Indiv.
                </a>
            <?php endif; ?>

            <!-- 6. Add Opponent Individual Stats -->
            <a href="<?= $this->Url->build([
                'controller' => 'StatBasketGameOpponent',
                'action' => 'add',
                $game->id,
            ]) ?>" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-plus-circle"></i> Add Opp. Indiv.
            </a>

            <!-- 7. Team Stats (Dead Ball Rebounds, Turnovers, etc.) -->
            <a href="<?= $this->Url->build([
                'controller' => 'StatBasketGameTeam',
                'action' => 'edit',
                $game->id,
            ]) ?>" class="btn btn-sm <?= $hasTeamTeamStats ? 'btn-info' : 'btn-outline-info' ?>">
                <i class="bi bi-bar-chart"></i> <?= $hasTeamTeamStats ? 'Edit' : 'Add' ?> Team Stats
            </a>
        </div>
    </div>
</div>

<?php if (!$hasAnyStats) : ?>
    <div class="row mt-2">
        <div class="col-12">
            <div class="alert alert-info text-center">
                <p class="mb-2"><strong>No game statistics entered yet</strong></p>
                <p class="mb-0 text-muted">Use the buttons above to add statistics for this game.</p>
            </div>
        </div>
    </div>
<?php else : ?>
    <!-- Team Player Stats -->
    <?php if (!empty($teamBoxStats) || $hasTeamStats) : ?>
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
                            <?php foreach ($teamColumnsWithData as $key => $label) : ?>
                                <th><?= h($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hasTeamStats) : ?>
                            <?php foreach ($playerStats as $stat) : ?>
                                <tr>
                                    <td><?= h($stat->team_season_roster->roster_number ?? '') ?></td>
                                    <td><?= h($stat->team_season_roster->person->display ?? $stat->team_season_roster->person->full ?? '') ?></td>
                                    <td><?= $stat->GS ? h($stat->team_season_roster->roster_position ?? '') : '' ?></td>
                                    <?php foreach ($teamColumnsWithData as $key => $label) : ?>
                                        <td><?= h($stat->$key ?? '') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($teamBoxStats)) : ?>
                        <?php if (!empty($teamTeamStats)) : ?>
                            <tbody class="table-light">
                                <tr>
                                    <td colspan="3"><strong>TEAM</strong></td>
                                    <?php foreach ($teamColumnsWithData as $key => $label) : ?>
                                        <td>
                                            <?php
                                            // Only show team stats for specific columns (rebounds, turnovers, points)
                                            if (in_array($key, ['ORB', 'DRB', 'RB', 'TRN', 'PTS'])) {
                                                echo h($teamTeamStats->$key ?? '0');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        <?php endif; ?>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="3"><strong>TOTALS</strong></td>
                                <?php foreach ($teamColumnsWithData as $key => $label) : ?>
                                    <td>
                                        <?php
                                        if ($key === 'MIN') {
                                            echo '-';
                                        } elseif ($key === 'PTS') {
                                            echo '<strong>' . h($game->pts_mur ?? '0') . '</strong>';
                                        } else {
                                            echo h($teamBoxStats[$key] ?? '0');
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
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
    <?php endif; ?>

    <!-- Opponent Player Stats -->
    <?php if (!empty($opponentBoxStats) || $hasOpponentStats) : ?>
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
                            <?php foreach ($opponentColumnsWithData as $key => $label) : ?>
                                <th><?= h($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hasOpponentStats) : ?>
                            <?php foreach ($opponentPlayerStats as $stat) : ?>
                                <tr>
                                    <td><?= h($stat->jersey ?? '') ?></td>
                                    <td><?= h($stat->name ?? '') ?></td>
                                    <td><?= h($stat->position ?? '') ?></td>
                                    <?php foreach ($opponentColumnsWithData as $key => $label) : ?>
                                        <td><?= h($stat->$key ?? '') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($opponentBoxStats)) : ?>
                        <?php if (!empty($opponentTeamStats)) : ?>
                            <tbody class="table-light">
                                <tr>
                                    <td colspan="3"><strong>TEAM</strong></td>
                                    <?php foreach ($opponentColumnsWithData as $key => $label) : ?>
                                        <td>
                                            <?php
                                            // Only show team stats for specific columns (rebounds, turnovers, points)
                                            if (in_array($key, ['ORB', 'DRB', 'RB', 'TRN', 'PTS'])) {
                                                echo h($opponentTeamStats->$key ?? '0');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        <?php endif; ?>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="3"><strong>TOTALS</strong></td>
                                <?php foreach ($opponentColumnsWithData as $key => $label) : ?>
                                    <td>
                                        <?php
                                        if ($key === 'MIN') {
                                            echo '-';
                                        } elseif ($key === 'PTS') {
                                            echo '<strong>' . h($game->pts_opp ?? '0') . '</strong>';
                                        } else {
                                            echo h($opponentBoxStats[$key] ?? '0');
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
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

    <!-- Initialize DataTables (assets provided by admin layout) -->
    <script>
        $(document).ready(function() {
            // Initialize DataTables for player stats tables
            if ($('#team-player-stats').length && !$.fn.DataTable.isDataTable('#team-player-stats')) {
                $('#team-player-stats').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    language: {
                        emptyTable: 'No player statistics entered yet'
                    },
                    order: [[<?= count($teamColumnsWithData) + 2 ?>, 'desc']], // Sort by last column (PTS) descending
                    columnDefs: [
                        { targets: [0, 1, 2], orderable: true },
                        { targets: '_all', orderable: true }
                    ]
                });
            }

            if ($('#opponent-player-stats').length && !$.fn.DataTable.isDataTable('#opponent-player-stats')) {
                $('#opponent-player-stats').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    language: {
                        emptyTable: 'No opponent player statistics entered yet'
                    },
                    order: [[<?= count($opponentColumnsWithData) + 2 ?>, 'desc']], // Sort by last column (PTS) descending
                    columnDefs: [
                        { targets: [0, 1, 2], orderable: true },
                        { targets: '_all', orderable: true }
                    ]
                });
            }
        });
    </script>
<?php endif; ?>
