<?php
/**
 * Basketball Season Stats Element
 *
 * Displays basketball season statistics with:
 * - Player stats with jersey number and name
 * - Team totals footer row
 * - Opponent stats footer row
 * - DataTable with sorting
 * - Dynamic column filtering (hides empty columns)
 * - Add/Edit buttons
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var \App\Model\Entity\StatBasketSeasonTeam|null $teamStats
 * @var \App\Model\Entity\StatBasketSeasonOpponent|null $opponentStats
 */

// Define all possible stat columns
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

// Determine which columns have data
$columnsWithData = [];
foreach ($allColumns as $key => $label) {
    $hasData = false;

    // Check player stats
    if ($playerStats) {
        foreach ($playerStats as $stat) {
            if (!empty($stat->$key)) {
                $hasData = true;
                break;
            }
        }
    }

    // Check team stats
    if (!$hasData && $teamStats && !empty($teamStats->$key)) {
        $hasData = true;
    }

    // Check opponent stats
    if (!$hasData && $opponentStats && !empty($opponentStats->$key)) {
        $hasData = true;
    }

    if ($hasData) {
        $columnsWithData[$key] = $label;
    }
}

$hasStats = $playerStats && $playerStats->count() > 0;
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Season Statistics</h3>
                <div class="btn-group">
                    <?= $this->Html->link(
                        '<i class="bi bi-plus-circle"></i> Add Player Stats',
                        ['controller' => 'StatBasketSeasonPerson', 'action' => 'add', $teamSeason->id],
                        ['class' => 'btn btn-sm btn-primary', 'escape' => false],
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="bi bi-pencil"></i> Edit Team Stats',
                        ['controller' => 'StatBasketSeasonTeam', 'action' => 'edit', $teamSeason->id],
                        ['class' => 'btn btn-sm btn-secondary', 'escape' => false],
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="bi bi-pencil"></i> Edit Opponent Stats',
                        ['controller' => 'StatBasketSeasonOpponent', 'action' => 'edit', $teamSeason->id],
                        ['class' => 'btn btn-sm btn-secondary', 'escape' => false],
                    ) ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$hasStats && !$teamStats && !$opponentStats) : ?>
                    <p class="text-muted text-center py-4">No stats available</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table id="season-stats-table" class="table table-striped table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Player</th>
                                    <?php foreach ($columnsWithData as $label) : ?>
                                        <th><?= h($label) ?></th>
                                    <?php endforeach; ?>
                                    <th>Actions</th>
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
                                            <td class="text-end">
                                                <?= $this->Html->link(
                                                    '<i class="bi bi-pencil"></i>',
                                                    ['controller' => 'StatBasketSeasonPerson', 'action' => 'edit', $stat->id],
                                                    ['class' => 'btn btn-sm btn-outline-primary', 'escape' => false, 'title' => 'Edit'],
                                                ) ?>
                                                <?= $this->Form->postLink(
                                                    '<i class="bi bi-trash"></i>',
                                                    ['controller' => 'StatBasketSeasonPerson', 'action' => 'delete', $stat->id],
                                                    ['class' => 'btn btn-sm btn-outline-danger', 'escape' => false, 'title' => 'Delete', 'confirm' => 'Delete this player stat?'],
                                                ) ?>
                                            </td>
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
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($opponentStats) : ?>
                                    <tr class="table-warning fw-bold">
                                        <td colspan="2">OPPONENT TOTALS</td>
                                        <?php foreach ($columnsWithData as $key => $label) : ?>
                                            <td><?= h($opponentStats->$key ?? '') ?></td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($hasStats || $teamStats || $opponentStats) : ?>
    <?php $this->append('script'); ?>
<script>
$(document).ready(function() {
    $('#season-stats-table').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "order": [[0, 'asc']], // Sort by jersey number
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disable sorting on actions column
        ]
    });
});
</script>
    <?php $this->end(); ?>
<?php endif; ?>
