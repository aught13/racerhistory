<?php
/**
 * Element: Admin/basketball_person_stats
 * Shows per-game stats (summed across periods) and season totals for a rostered player.
 * Expects:
 * - array $gameStats: entries of ['game' => Game, 'stats' => StatBasketGamePerson[]]
 * - object|null $seasonStats: StatBasketSeasonPerson
 *
 * @var \App\View\AppView $this
 * @var mixed $gameStats
 * @var object $seasonStats
 * @var mixed $value
 */
?>
<?php if (!empty($gameStats)) : ?>
<h5 class="mb-3"><i class="bi bi-graph-up"></i> Game Stats</h5>
<div class="table-responsive mb-3">
    <table class="table table-sm table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Opponent</th>
                <th>MIN</th>
                <th>FGM-A</th>
                <th>3PM-A</th>
                <th>FTM-A</th>
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
            <?php foreach ($gameStats as $gameData) : ?>
                <?php
                $game = $gameData['game'];
                $stats = $gameData['stats'];
                $totals = [
                    'MIN' => 0, 'FGM' => 0, 'FGA' => 0,
                    'TPM' => 0, 'TPA' => 0, 'FTM' => 0, 'FTA' => 0,
                    'RB' => 0, 'AST' => 0, 'STL' => 0, 'BS' => 0,
                    'TRN' => 0, 'PF' => 0, 'PTS' => 0,
                ];
                foreach ($stats as $stat) {
                    foreach ($totals as $field => &$value) {
                        $value += is_numeric($stat->$field ?? null) ? (int)$stat->$field : 0;
                    }
                    unset($value);
                }
                ?>
                <tr>
                    <td>
                        <?= $game->game_date instanceof DateTimeInterface
                            ? h($game->game_date->format('M j, Y'))
                            : 'N/A' ?>
                    </td>
                    <td><?= h($game->opponent->opponent_name ?? 'Unknown') ?></td>
                    <td><?= h($totals['MIN']) ?></td>
                    <td><?= h($totals['FGM']) ?>-<?= h($totals['FGA']) ?></td>
                    <td><?= h($totals['TPM']) ?>-<?= h($totals['TPA']) ?></td>
                    <td><?= h($totals['FTM']) ?>-<?= h($totals['FTA']) ?></td>
                    <td><?= h($totals['RB']) ?></td>
                    <td><?= h($totals['AST']) ?></td>
                    <td><?= h($totals['STL']) ?></td>
                    <td><?= h($totals['BS']) ?></td>
                    <td><?= h($totals['TRN']) ?></td>
                    <td><?= h($totals['PF']) ?></td>
                    <td><?= h($totals['PTS']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($seasonStats)) : ?>
        <tfoot class="table-secondary fw-bold">
            <tr>
                <td colspan="2">Season Totals</td>
                <td><?= h($seasonStats->MIN ?? 0) ?></td>
                <td><?= h($seasonStats->FGM ?? 0) ?>-<?= h($seasonStats->FGA ?? 0) ?></td>
                <td><?= h($seasonStats->TPM ?? 0) ?>-<?= h($seasonStats->TPA ?? 0) ?></td>
                <td><?= h($seasonStats->FTM ?? 0) ?>-<?= h($seasonStats->FTA ?? 0) ?></td>
                <td><?= h($seasonStats->RB ?? 0) ?></td>
                <td><?= h($seasonStats->AST ?? 0) ?></td>
                <td><?= h($seasonStats->STL ?? 0) ?></td>
                <td><?= h($seasonStats->BS ?? 0) ?></td>
                <td><?= h($seasonStats->TRN ?? 0) ?></td>
                <td><?= h($seasonStats->PF ?? 0) ?></td>
                <td><?= h($seasonStats->PTS ?? 0) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php endif; ?>
