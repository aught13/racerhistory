<?php
declare(strict_types=1);

/**
 * Public Basketball Season Stats Element
 *
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var object|null $teamStats
 * @var object|null $opponentStats
 * @var array<string,string> $statsColumns
 */

$statsColumns = $statsColumns ?? [];
$playerRows = $playerStats ? $playerStats->toArray() : [];
$hasStats = !empty($playerRows);
$advancedShootingPlayers = [];
$teamShootingTotals = null;
$teamTotalsName = __('Team Totals');

foreach ($playerRows as $stat) {
    $roster = $stat->team_season_roster ?? null;
    $person = $roster ? ($roster->person ?? null) : null;
    $name = '';
    if ($person) {
        $display = $person->display ?? null;
        if ($display) {
            $name = $display;
        } else {
            $first = $person->first_name ?? '';
            $last = $person->last_name ?? '';
            $name = trim($first . ' ' . $last);
        }
    }

    $advancedShootingPlayers[] = [
        'name' => $name,
        'GP' => (int)($stat->GP ?? 0),
        'FGM' => (int)($stat->FGM ?? 0),
        'FGA' => (int)($stat->FGA ?? 0),
        'TPM' => (int)($stat->TPM ?? 0),
        'TPA' => (int)($stat->TPA ?? 0),
        'FTM' => (int)($stat->FTM ?? 0),
        'FTA' => (int)($stat->FTA ?? 0),
        'PTS' => (int)($stat->PTS ?? 0),
    ];
}

if ($teamStats) {
    $teamShootingTotals = [
        'name' => $teamTotalsName,
        'GP' => (int)($teamStats->GP ?? 0),
        'FGM' => (int)($teamStats->FGM ?? 0),
        'FGA' => (int)($teamStats->FGA ?? 0),
        'TPM' => (int)($teamStats->TPM ?? 0),
        'TPA' => (int)($teamStats->TPA ?? 0),
        'FTM' => (int)($teamStats->FTM ?? 0),
        'FTA' => (int)($teamStats->FTA ?? 0),
        'PTS' => (int)($teamStats->PTS ?? 0),
    ];
}

$hasAdvancedShooting = false;
foreach ($advancedShootingPlayers as $row) {
    if ($row['FGA'] > 0 && $row['FTA'] > 0) {
        $hasAdvancedShooting = true;
        break;
    }
}
if (!$hasAdvancedShooting && $teamShootingTotals) {
    if ($teamShootingTotals['FGA'] > 0 && $teamShootingTotals['FTA'] > 0) {
        $hasAdvancedShooting = true;
    }
}

$advancedShootingJson = '';
if ($hasAdvancedShooting) {
    $payload = [
        'players' => $advancedShootingPlayers,
        'teamTotals' => $teamShootingTotals,
    ];
    $encoded = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $advancedShootingJson = $encoded !== false ? $encoded : '';
}
?>

<?php if (!$hasStats && !$teamStats && !$opponentStats) : ?>
    <p class="text-muted mb-0">No stats available.</p>
<?php else : ?>
    <div class="season-stats-tabs" data-season-stats-tabs>
        <div class="nav nav-tabs season-stats-tabs__nav" role="tablist">
            <button class="nav-link active" type="button" data-season-stats-tab="general" aria-selected="true">General Stats</button>
            <?php if ($hasAdvancedShooting) : ?>
                <button class="nav-link" type="button" data-season-stats-tab="advanced" aria-selected="false">Advanced Shooting</button>
            <?php endif; ?>
        </div>
        <div class="tab-content mt-3 season-stats-tabs__panels">
            <div class="tab-pane active" data-season-stats-panel="general">
                <div class="table-responsive">
                    <table id="season-stats-table" class="table table-striped table-bordered table-sm js-datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <?php foreach ($statsColumns as $label) : ?>
                                    <th><?= h($label) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($playerRows)) : ?>
                                <?php foreach ($playerRows as $stat) : ?>
                                    <tr>
                                        <td><?= h($stat->team_season_roster->roster_number ?? '') ?></td>
                                        <td>
                                            <?php
                                            $person = $stat->team_season_roster->person ?? null;
                                            $name = $person ? ($person->display ?? $person->full) : '';
                                            echo h($name);
                                            ?>
                                        </td>
                                        <?php foreach ($statsColumns as $key => $label) : ?>
                                            <td><?= h($stat->$key ?? '') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <?php if ($teamStats) : ?>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2">TEAM TOTALS</td>
                                    <?php foreach ($statsColumns as $key => $label) : ?>
                                        <td><?= h($teamStats->$key ?? '') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                            <?php if ($opponentStats) : ?>
                                <tr class="table-warning fw-bold">
                                    <td colspan="2">OPPONENT TOTALS</td>
                                    <?php foreach ($statsColumns as $key => $label) : ?>
                                        <td><?= h($opponentStats->$key ?? '') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php if ($hasAdvancedShooting) : ?>
                <div class="tab-pane d-none" data-season-stats-panel="advanced" data-season-advanced-stats="<?= h($advancedShootingJson) ?>">
                    <div class="table-responsive" data-season-advanced-table-container>
                        <p class="text-muted mb-0" data-season-advanced-placeholder>Advanced shooting metrics load when you view this tab.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
