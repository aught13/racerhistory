<?php
declare(strict_types=1);

/**
 * Public Basketball Season Stats Element
 *
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var object|null $teamStats
 * @var object|null $opponentStats
 */

$allColumns = [
    'GP' => 'GP',
    'GS' => 'GS',
    'MIN' => 'MIN',
    'FGM' => 'FGM',
    'FGA' => 'FGA',
    'TPM' => '3PM',
    'TPA' => '3PA',
    'FTM' => 'FTM',
    'FTA' => 'FTA',
    'ORB' => 'ORB',
    'DRB' => 'DRB',
    'RB' => 'RB',
    'AST' => 'AST',
    'STL' => 'STL',
    'BS' => 'BS',
    'TRN' => 'TRN',
    'PF' => 'PF',
    'TF' => 'TF',
    'PTS' => 'PTS',
];

$columnsWithData = [];
foreach ($allColumns as $key => $label) {
    $hasData = false;

    if ($playerStats) {
        foreach ($playerStats as $stat) {
            if (!empty($stat->$key)) {
                $hasData = true;
                break;
            }
        }
    }

    if (!$hasData && $teamStats && !empty($teamStats->$key)) {
        $hasData = true;
    }

    if (!$hasData && $opponentStats && !empty($opponentStats->$key)) {
        $hasData = true;
    }

    if ($hasData) {
        $columnsWithData[$key] = $label;
    }
}

$hasStats = $playerStats && $playerStats->count() > 0;
?>

<?php if (!$hasStats && !$teamStats && !$opponentStats) : ?>
    <p class="text-muted mb-0">No stats available.</p>
<?php else : ?>
    <div class="table-responsive">
        <table id="season-stats-table" class="table table-striped table-bordered table-sm js-datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <?php foreach ($columnsWithData as $label) : ?>
                        <th><?= h($label) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($playerStats) : ?>
                    <?php foreach ($playerStats as $stat) : ?>
                        <tr>
                            <td><?= h($stat->team_season_roster->roster_number ?? '') ?></td>
                            <td>
                                <?php
                                $person = $stat->team_season_roster->person ?? null;
                                $name = $person ? ($person->display ?? $person->full) : '';
                                echo h($name);
                                ?>
                            </td>
                            <?php foreach ($columnsWithData as $key => $label) : ?>
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
                        <?php foreach ($columnsWithData as $key => $label) : ?>
                            <td><?= h($teamStats->$key ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
                <?php if ($opponentStats) : ?>
                    <tr class="table-warning fw-bold">
                        <td colspan="2">OPPONENT TOTALS</td>
                        <?php foreach ($columnsWithData as $key => $label) : ?>
                            <td><?= h($opponentStats->$key ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
