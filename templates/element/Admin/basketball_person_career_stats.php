<?php
/**
 * Element: Admin/basketball_person_career_stats
 * Shows season rows and aggregated career totals for a player.
 * Expects:
 * - array $seasons: entries of ['teamSeason' => TeamSeason, 'stats' => StatBasketSeasonPerson]
 * - array $totals: accumulated totals fields
 * - int|null $minYear, $maxYear
 */
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Season</th>
                <th>GP</th>
                <th>GS</th>
                <th>MIN</th>
                <th>FGM-A</th>
                <th>FG%</th>
                <th>3PM-A</th>
                <th>3P%</th>
                <th>FTM-A</th>
                <th>FT%</th>
                <th>REB</th>
                <th>AST</th>
                <th>STL</th>
                <th>BLK</th>
                <th>TO</th>
                <th>PF</th>
                <th>PTS</th>
                <th>PPG</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seasons as $seasonData) : ?>
                <?php $stats = $seasonData['stats']; ?>
                <?php $ts = $seasonData['teamSeason']; ?>
                <tr>
                    <td class="fw-semibold"><?= h($ts->season->start ?? '????') ?>-<?= h($ts->season->end ?? '????') ?></td>
                    <td><?= h($stats->GP ?? 0) ?></td>
                    <td><?= h($stats->GS ?? 0) ?></td>
                    <td><?= h($stats->MIN ?? 0) ?></td>
                    <td><?= h($stats->FGM ?? 0) ?>-<?= h($stats->FGA ?? 0) ?></td>
                    <td class="text-primary">
                        <?= ($stats->FGA ?? 0) > 0
                            ? number_format(($stats->FGM ?? 0) / ($stats->FGA ?? 0) * 100, 1)
                            : '0.0' ?>%
                    </td>
                    <td><?= h($stats->TPM ?? 0) ?>-<?= h($stats->TPA ?? 0) ?></td>
                    <td class="text-primary">
                        <?= ($stats->TPA ?? 0) > 0
                            ? number_format(($stats->TPM ?? 0) / ($stats->TPA ?? 0) * 100, 1)
                            : '0.0' ?>%
                    </td>
                    <td><?= h($stats->FTM ?? 0) ?>-<?= h($stats->FTA ?? 0) ?></td>
                    <td class="text-primary">
                        <?= ($stats->FTA ?? 0) > 0
                            ? number_format(($stats->FTM ?? 0) / ($stats->FTA ?? 0) * 100, 1)
                            : '0.0' ?>%
                    </td>
                    <td><?= h($stats->RB ?? 0) ?></td>
                    <td><?= h($stats->AST ?? 0) ?></td>
                    <td><?= h($stats->STL ?? 0) ?></td>
                    <td><?= h($stats->BS ?? 0) ?></td>
                    <td><?= h($stats->TRN ?? 0) ?></td>
                    <td><?= h($stats->PF ?? 0) ?></td>
                    <td class="text-success"><?= h($stats->PTS ?? 0) ?></td>
                    <td class="text-success">
                        <?= ($stats->GP ?? 0) > 0
                            ? number_format(($stats->PTS ?? 0) / ($stats->GP ?? 0), 1)
                            : '0.0' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-dark">
            <tr class="fw-bold">
                <td class="text-center"><?= ($minYear ?? '????') ?>-<?= ($maxYear ?? '????') ?></td>
                <td><?= h($totals['GP'] ?? 0) ?></td>
                <td><?= h($totals['GS'] ?? 0) ?></td>
                <td><?= h($totals['MIN'] ?? 0) ?></td>
                <td><?= h(($totals['FGM'] ?? 0)) ?>-<?= h(($totals['FGA'] ?? 0)) ?></td>
                <td class="text-warning"><?= ($totals['FGA'] ?? 0) > 0 ? number_format(($totals['FGM'] ?? 0) / ($totals['FGA'] ?? 0) * 100, 1) : '0.0' ?>%</td>
                <td><?= h(($totals['TPM'] ?? 0)) ?>-<?= h(($totals['TPA'] ?? 0)) ?></td>
                <td class="text-warning"><?= ($totals['TPA'] ?? 0) > 0 ? number_format(($totals['TPM'] ?? 0) / ($totals['TPA'] ?? 0) * 100, 1) : '0.0' ?>%</td>
                <td><?= h(($totals['FTM'] ?? 0)) ?>-<?= h(($totals['FTA'] ?? 0)) ?></td>
                <td class="text-warning"><?= ($totals['FTA'] ?? 0) > 0 ? number_format(($totals['FTM'] ?? 0) / ($totals['FTA'] ?? 0) * 100, 1) : '0.0' ?>%</td>
                <td><?= h(($totals['RB'] ?? 0)) ?></td>
                <td><?= h(($totals['AST'] ?? 0)) ?></td>
                <td><?= h(($totals['STL'] ?? 0)) ?></td>
                <td><?= h(($totals['BS'] ?? 0)) ?></td>
                <td><?= h(($totals['TRN'] ?? 0)) ?></td>
                <td><?= h(($totals['PF'] ?? 0)) ?></td>
                <td class="text-warning"><?= h(($totals['PTS'] ?? 0)) ?></td>
                <td class="text-warning"><?= ($totals['GP'] ?? 0) > 0 ? number_format(($totals['PTS'] ?? 0) / ($totals['GP'] ?? 0), 1) : '0.0' ?></td>
            </tr>
        </tfoot>
    </table>
</div>
