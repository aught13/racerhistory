<?php
declare(strict_types=1);

/**
 * Public Basketball Season Stats Element
 *
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var object|null $teamStats
 * @var object|null $opponentStats
 * @var \App\View\AppView $this
 */

$playerRows = $playerStats ? $playerStats->toArray() : [];

$makeBoxScoreRow = static function (object $stats, string $name): array {
    $advancedStats = $stats->advanced_stats ?? [];
    if (!is_array($advancedStats)) {
        $advancedStats = [];
    }

    return [
        'name' => $name,
        'personId' => 0,
        'GS' => $stats->GS ?? null,
        'GP' => $stats->GP ?? null,
        'MIN' => $stats->MIN ?? null,
        'FGM' => $stats->FGM ?? null,
        'FGA' => $stats->FGA ?? null,
        'TPM' => $stats->TPM ?? null,
        'TPA' => $stats->TPA ?? null,
        'FTM' => $stats->FTM ?? null,
        'FTA' => $stats->FTA ?? null,
        'ORB' => $stats->ORB ?? null,
        'DRB' => $stats->DRB ?? null,
        'RB' => $stats->RB ?? null,
        'AST' => $stats->AST ?? null,
        'STL' => $stats->STL ?? null,
        'BS' => $stats->BS ?? null,
        'TRN' => $stats->TRN ?? null,
        'PF' => $stats->PF ?? null,
        'PTS' => $stats->PTS ?? null,
        'advanced' => $advancedStats,
    ];
};

$seasonPlayers = [];
foreach ($playerRows as $stat) {
    $person = $stat->team_season_roster->person ?? null;
    $name = '';
    if ($person) {
        $name = (string)($person->display
            ?? $person->full
            ?? trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? '')));
    }
    if ($name === '') {
        $name = 'Unknown';
    }

    $seasonPlayers[] = $makeBoxScoreRow($stat, $name);
    $seasonPlayers[array_key_last($seasonPlayers)]['personId'] = (int)($person->id ?? 0);
}

$teamTotals = $teamStats
    ? $makeBoxScoreRow($teamStats, __('TEAM TOTALS'))
    : null;
$opponentTotals = $opponentStats
    ? $makeBoxScoreRow($opponentStats, __('OPPONENT TOTALS'))
    : null;

$basicColumns = [
    ['label' => 'GP', 'key' => 'GP', 'format' => 'games'],
    ['label' => 'GS', 'key' => 'GS', 'format' => 'count'],
    ['label' => 'MP', 'key' => 'MIN', 'format' => 'minutes'],
    ['label' => 'FG', 'key' => 'FGM', 'format' => 'count'],
    ['label' => 'FGA', 'key' => 'FGA', 'format' => 'count'],
    ['label' => 'FG%', 'key' => 'FG%', 'format' => 'percent', 'source' => 'advanced'],
    ['label' => '2P', 'key' => '2PM', 'format' => 'count', 'source' => 'advanced'],
    ['label' => '2PA', 'key' => '2PA', 'format' => 'count', 'source' => 'advanced'],
    ['label' => '2P%', 'key' => '2P%', 'format' => 'percent', 'source' => 'advanced'],
    ['label' => '3P', 'key' => 'TPM', 'format' => 'count'],
    ['label' => '3PA', 'key' => 'TPA', 'format' => 'count'],
    ['label' => '3P%', 'key' => '3P%', 'format' => 'percent', 'source' => 'advanced'],
    ['label' => 'FT', 'key' => 'FTM', 'format' => 'count'],
    ['label' => 'FTA', 'key' => 'FTA', 'format' => 'count'],
    ['label' => 'FT%', 'key' => 'FT%', 'format' => 'percent', 'source' => 'advanced'],
    ['label' => 'ORB', 'key' => 'ORB', 'format' => 'count'],
    ['label' => 'DRB', 'key' => 'DRB', 'format' => 'count'],
    ['label' => 'TRB', 'key' => 'RB', 'format' => 'count'],
    ['label' => 'AST', 'key' => 'AST', 'format' => 'count'],
    ['label' => 'STL', 'key' => 'STL', 'format' => 'count'],
    ['label' => 'BLK', 'key' => 'BS', 'format' => 'count'],
    ['label' => 'TOV', 'key' => 'TRN', 'format' => 'count'],
    ['label' => 'PF', 'key' => 'PF', 'format' => 'count'],
    ['label' => 'PTS', 'key' => 'PTS', 'format' => 'count'],
];

$advancedColumns = [
    ['label' => 'GP', 'key' => 'GP', 'format' => 'games'],
    ['label' => 'GS', 'key' => 'GS', 'format' => 'count'],
    ['label' => 'MP', 'key' => 'MIN', 'format' => 'minutes'],
    ['label' => 'TS%', 'key' => 'TS%', 'format' => 'percent'],
    ['label' => 'eFG%', 'key' => 'eFG%', 'format' => 'percent'],
    ['label' => '3PAr', 'key' => '3PAr', 'format' => 'ratio'],
    ['label' => 'FTr', 'key' => 'FTr', 'format' => 'ratio'],
    ['label' => 'ORB%', 'key' => 'ORB%', 'format' => 'percent'],
    ['label' => 'DRB%', 'key' => 'DRB%', 'format' => 'percent'],
    ['label' => 'TRB%', 'key' => 'TRB%', 'format' => 'percent'],
    ['label' => 'AST%', 'key' => 'AST%', 'format' => 'percent'],
    ['label' => 'STL%', 'key' => 'STL%', 'format' => 'percent'],
    ['label' => 'BLK%', 'key' => 'BLK%', 'format' => 'percent'],
    ['label' => 'TOV%', 'key' => 'TOV%', 'format' => 'percent'],
    ['label' => 'USG%', 'key' => 'USG%', 'format' => 'percent'],
    ['label' => 'PProd', 'key' => 'PProd', 'format' => 'decimal'],
];

$formatBoxValue = static function ($value, string $format, bool $perGame = false, $games = null): string {
    if (!is_numeric($value)) {
        return '—';
    }
    if ($perGame && in_array($format, ['count', 'minutes'], true)) {
        if (!is_numeric($games) || (float)$games <= 0) {
            return '—';
        }
        $value = (float)$value / (float)$games;
    }

    return match ($format) {
        'percent' => number_format((float)$value * 100, 1, '.', '') . '%',
        'ratio' => number_format((float)$value, 3, '.', ''),
        'games' => number_format((float)$value, 0, '.', ''),
        'minutes' => number_format((float)$value, $perGame ? 1 : 2, '.', ''),
        'decimal' => number_format((float)$value, 1, '.', ''),
        'count' => number_format((float)$value, $perGame ? 1 : 0, '.', ''),
        default => '—',
    };
};

$resolveBoxValue = static function (array $row, array $column): mixed {
    $key = $column['key'];
    if (($column['source'] ?? null) === 'advanced') {
        $advancedValue = $row['advanced'][$key] ?? null;
        if (is_numeric($advancedValue)) {
            return $advancedValue;
        }
    } elseif (is_numeric($row[$key] ?? null)) {
        return $row[$key];
    }

    if (isset($row['advanced'][$key]) && is_numeric($row['advanced'][$key])) {
        return $row['advanced'][$key];
    }

    if (isset($row['advanced'][$key])) {
        return null;
    }

    if (str_ends_with($key, '%')) {
        $pair = match ($key) {
            'FG%' => ['FGM', 'FGA'],
            '2P%' => ['2PM', '2PA'],
            '3P%' => ['TPM', 'TPA'],
            'FT%' => ['FTM', 'FTA'],
            default => null,
        };
        if ($pair !== null) {
            [$madeKey, $attemptedKey] = $pair;
            $made = $madeKey === '2PM'
                ? (is_numeric($row['FGM'] ?? null) && is_numeric($row['TPM'] ?? null)
                    ? (float)$row['FGM'] - (float)$row['TPM']
                    : null)
                : ($row[$madeKey] ?? null);
            $attempted = $attemptedKey === '2PA'
                ? (is_numeric($row['FGA'] ?? null) && is_numeric($row['TPA'] ?? null)
                    ? (float)$row['FGA'] - (float)$row['TPA']
                    : null)
                : ($row[$attemptedKey] ?? null);
            if (is_numeric($made) && is_numeric($attempted) && (float)$attempted > 0) {
                return (float)$made / (float)$attempted;
            }
        }
    }

    if ($key === '2PM' && is_numeric($row['FGM'] ?? null) && is_numeric($row['TPM'] ?? null)) {
        return max((float)$row['FGM'] - (float)$row['TPM'], 0);
    }
    if ($key === '2PA' && is_numeric($row['FGA'] ?? null) && is_numeric($row['TPA'] ?? null)) {
        return max((float)$row['FGA'] - (float)$row['TPA'], 0);
    }

    return $row[$key] ?? null;
};

$resolveAdvancedValue = static function (array $row, array $column): mixed {
    if (in_array($column['key'], ['GP', 'GS', 'MIN'], true)) {
        return $row[$column['key']] ?? null;
    }

    return $row['advanced'][$column['key']] ?? null;
};

$filterRowsWithData = static function (array $rows, array $columns, callable $resolveValue): array {
    $filteredRows = [];
    foreach ($rows as $row) {
        foreach ($columns as $column) {
            if (is_numeric($resolveValue($row, $column))) {
                $filteredRows[] = $row;
                break;
            }
        }
    }

    return $filteredRows;
};

$filterColumnsWithData = static function (
    array $columns,
    array $rows,
    array $totals,
    callable $resolveValue,
): array {
    $resultRows = array_merge($rows, $totals);
    $filteredColumns = [];
    foreach ($columns as $column) {
        foreach ($resultRows as $row) {
            if (is_numeric($resolveValue($row, $column))) {
                $filteredColumns[] = $column;
                break;
            }
        }
    }

    return $filteredColumns;
};

$boxScoreTotals = [];
foreach ([$teamTotals, $opponentTotals] as $totalRow) {
    if ($totalRow !== null) {
        $boxScoreTotals[] = $totalRow;
    }
}

$basicRows = $filterRowsWithData($seasonPlayers, $basicColumns, $resolveBoxValue);
$basicTotals = $filterRowsWithData($boxScoreTotals, $basicColumns, $resolveBoxValue);
$basicColumns = $filterColumnsWithData($basicColumns, $basicRows, $basicTotals, $resolveBoxValue);

$statsTabs = [];
if ($basicRows !== [] || $basicTotals !== []) {
    $statsTabs[] = [
        'id' => 'basic',
        'label' => 'Basic Box Score Stats',
        'tableId' => 'season-stats-table',
        'columns' => $basicColumns,
        'rows' => $basicRows,
        'totals' => $basicTotals,
        'perGame' => false,
        'active' => true,
    ];
}

$hasGames = static function (array $row): bool {
    return is_numeric($row['GP'] ?? null) && (float)$row['GP'] > 0;
};
$perGameCandidates = array_values(array_filter($seasonPlayers, $hasGames));
$perGameTotalCandidates = array_values(array_filter($boxScoreTotals, $hasGames));
$perGameRows = $filterRowsWithData($perGameCandidates, $basicColumns, $resolveBoxValue);
$perGameTotals = $filterRowsWithData($perGameTotalCandidates, $basicColumns, $resolveBoxValue);
$perGameColumns = $filterColumnsWithData($basicColumns, $perGameRows, $perGameTotals, $resolveBoxValue);
if ($perGameRows !== [] || $perGameTotals !== []) {
    $statsTabs[] = [
        'id' => 'per-game',
        'label' => 'Per Game',
        'tableId' => 'season-per-game-table',
        'columns' => $perGameColumns,
        'rows' => $perGameRows,
        'totals' => $perGameTotals,
        'perGame' => true,
        'active' => $statsTabs === [],
    ];
}

$advancedRows = $filterRowsWithData($seasonPlayers, $advancedColumns, $resolveAdvancedValue);
$advancedTotals = $teamTotals !== null
    ? $filterRowsWithData([$teamTotals], $advancedColumns, $resolveAdvancedValue)
    : [];
$advancedColumns = $filterColumnsWithData(
    $advancedColumns,
    $advancedRows,
    $advancedTotals,
    $resolveAdvancedValue,
);
$hasAdvancedMetrics = false;
foreach ($advancedColumns as $column) {
    if (!in_array($column['key'], ['GP', 'GS', 'MIN'], true)) {
        $hasAdvancedMetrics = true;
        break;
    }
}
$showAdvancedStats = $hasAdvancedMetrics && ($advancedRows !== [] || $advancedTotals !== []);
$hasStats = $statsTabs !== [] || $showAdvancedStats;
$activeTabId = $statsTabs[0]['id'] ?? ($showAdvancedStats ? 'advanced' : null);
?>

<?php if (!$hasStats) : ?>
    <p class="text-muted mb-0">No stats available.</p>
<?php else : ?>
    <div class="season-stats-tabs" data-season-stats-tabs>
        <div class="nav nav-tabs season-stats-tabs__nav" role="tablist">
            <?php foreach ($statsTabs as $tab) : ?>
                <button
                    class="nav-link<?= $tab['active'] ? ' active' : '' ?>"
                    type="button"
                    role="tab"
                    data-season-stats-tab="<?= h($tab['id']) ?>"
                    aria-selected="<?= $tab['active'] ? 'true' : 'false' ?>"
                ><?= h($tab['label']) ?></button>
            <?php endforeach; ?>
            <?php if ($showAdvancedStats) : ?>
                <button
                    class="nav-link<?= $activeTabId === 'advanced' ? ' active' : '' ?>"
                    type="button"
                    role="tab"
                    data-season-stats-tab="advanced"
                    aria-selected="<?= $activeTabId === 'advanced' ? 'true' : 'false' ?>"
                >Advanced Box Score Stats</button>
            <?php endif; ?>
        </div>

        <div class="tab-content mt-3 season-stats-tabs__panels">
            <?php foreach ($statsTabs as $tab) : ?>
                <div
                    class="tab-pane<?= $tab['active'] ? ' active' : ' d-none' ?>"
                    data-season-stats-panel="<?= h($tab['id']) ?>"
                >
                    <div class="table-responsive">
                        <table
                            id="<?= h($tab['tableId']) ?>"
                            class="table table-striped table-bordered table-sm small js-datatable"
                        >
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <?php foreach ($tab['columns'] as $column) : ?>
                                        <th><?= h($column['label']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tab['rows'] as $row) : ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['personId'] > 0) : ?>
                                                <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $row['personId']]) ?>" class="fw-bold text-body text-decoration-none">
                                                    <?= h($row['name']) ?>
                                                </a>
                                            <?php else : ?>
                                                <span class="fw-bold"><?= h($row['name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach ($tab['columns'] as $column) : ?>
                                            <?php
                                            $value = $resolveBoxValue($row, $column);
                                            ?>
                                            <td><?= h($formatBoxValue($value, $column['format'], $tab['perGame'], $row['GP'])) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($tab['totals'] !== []) : ?>
                                <tfoot>
                                    <?php foreach ($tab['totals'] as $totalRow) : ?>
                                        <tr class="table-secondary fw-bold">
                                            <td><?= h($totalRow['name']) ?></td>
                                            <?php foreach ($tab['columns'] as $column) : ?>
                                                <?php
                                                $value = $resolveBoxValue($totalRow, $column);
                                                ?>
                                                <td><?= h($formatBoxValue($value, $column['format'], $tab['perGame'], $totalRow['GP'])) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($showAdvancedStats) : ?>
                <div
                    class="tab-pane<?= $activeTabId === 'advanced' ? ' active' : ' d-none' ?>"
                    data-season-stats-panel="advanced"
                >
                    <div class="table-responsive">
                        <table
                            id="season-advanced-stats-table"
                            class="table table-striped table-bordered table-sm small js-datatable"
                            data-season-advanced-table
                        >
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <?php foreach ($advancedColumns as $column) : ?>
                                        <th><?= h($column['label']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advancedRows as $row) : ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['personId'] > 0) : ?>
                                                <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $row['personId']]) ?>" class="fw-bold text-body text-decoration-none">
                                                    <?= h($row['name']) ?>
                                                </a>
                                            <?php else : ?>
                                                <span class="fw-bold"><?= h($row['name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach ($advancedColumns as $column) : ?>
                                            <?php $value = $resolveAdvancedValue($row, $column); ?>
                                            <td><?= h($formatBoxValue($value, $column['format'])) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($advancedTotals !== []) : ?>
                                <tfoot>
                                    <?php foreach ($advancedTotals as $totalRow) : ?>
                                        <tr class="table-secondary fw-bold">
                                            <td><?= h($totalRow['name']) ?></td>
                                            <?php foreach ($advancedColumns as $column) : ?>
                                                <?php $value = $resolveAdvancedValue($totalRow, $column); ?>
                                                <td><?= h($formatBoxValue($value, $column['format'])) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
