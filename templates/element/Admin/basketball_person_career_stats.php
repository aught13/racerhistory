<?php
declare(strict_types=1);

/**
 * Basketball career box-score tables.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{teamSeason:\App\Model\Entity\TeamSeason,stats:\App\Model\Entity\StatBasketSeasonPerson}> $seasons
 * @var array<string,int> $totals
 * @var array<string,float|null> $advancedStats
 * @var int|null $minYear
 * @var int|null $maxYear
 */

$advancedStats = is_array($advancedStats ?? null) ? $advancedStats : [];
$readStat = static function (array|object $stats, string $key): mixed {
    if (is_array($stats)) {
        return $stats[$key] ?? null;
    }

    return $stats->{$key} ?? null;
};

$seasonRows = [];
foreach ($seasons as $seasonData) {
    $stats = $seasonData['stats'];
    $season = $seasonData['teamSeason']->season;
    $seasonAdvancedStats = $stats->get('advanced_stats');
    $seasonRows[] = [
        'label' => (string)($season->start ?? '????') . '-' . (string)($season->end ?? '????'),
        'stats' => $stats,
        'advanced' => is_array($seasonAdvancedStats) ? $seasonAdvancedStats : [],
    ];
}

$availableCareerFields = [];
foreach ($seasonRows as $row) {
    foreach (array_keys($totals) as $field) {
        if (is_numeric($readStat($row['stats'], $field))) {
            $availableCareerFields[$field] = true;
        }
    }
}
$careerTotals = $totals;
foreach ($careerTotals as $field => $value) {
    if (!isset($availableCareerFields[$field])) {
        $careerTotals[$field] = null;
    }
}

$careerRow = [
    'label' => (string)($minYear ?? '????') . '-' . (string)($maxYear ?? '????'),
    'stats' => $careerTotals,
    'advanced' => $advancedStats,
];

$basicColumns = [
    ['label' => 'GP', 'key' => 'GP', 'format' => 'count'],
    ['label' => 'GS', 'key' => 'GS', 'format' => 'count'],
    ['label' => 'MIN', 'key' => 'MIN', 'format' => 'minutes'],
    ['label' => 'FGM-A', 'pair' => ['FGM', 'FGA'], 'format' => 'pair'],
    ['label' => 'FG%', 'pair' => ['FGM', 'FGA'], 'format' => 'percent'],
    ['label' => '3PM-A', 'pair' => ['TPM', 'TPA'], 'format' => 'pair'],
    ['label' => '3P%', 'pair' => ['TPM', 'TPA'], 'format' => 'percent'],
    ['label' => 'FTM-A', 'pair' => ['FTM', 'FTA'], 'format' => 'pair'],
    ['label' => 'FT%', 'pair' => ['FTM', 'FTA'], 'format' => 'percent'],
    ['label' => 'REB', 'key' => 'RB', 'format' => 'count'],
    ['label' => 'AST', 'key' => 'AST', 'format' => 'count'],
    ['label' => 'STL', 'key' => 'STL', 'format' => 'count'],
    ['label' => 'BLK', 'key' => 'BS', 'format' => 'count'],
    ['label' => 'TO', 'key' => 'TRN', 'format' => 'count'],
    ['label' => 'PF', 'key' => 'PF', 'format' => 'count'],
    ['label' => 'PTS', 'key' => 'PTS', 'format' => 'count'],
    ['label' => 'PPG', 'key' => 'PPG', 'format' => 'ppg'],
];

$advancedColumns = [
    ['label' => 'GP', 'key' => 'GP', 'source' => 'stats', 'format' => 'count'],
    ['label' => 'GS', 'key' => 'GS', 'source' => 'stats', 'format' => 'count'],
    ['label' => 'MP', 'key' => 'MIN', 'source' => 'stats', 'format' => 'minutes'],
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

$hasBasicValue = static function (array $row, array $column) use ($readStat): bool {
    if (isset($column['pair'])) {
        [$madeKey, $attemptedKey] = $column['pair'];
        $made = $readStat($row['stats'], $madeKey);
        $attempted = $readStat($row['stats'], $attemptedKey);
        if ($column['format'] === 'percent') {
            return is_numeric($made) && is_numeric($attempted) && (float)$attempted > 0;
        }

        return is_numeric($made) && is_numeric($attempted);
    }
    if ($column['format'] === 'ppg') {
        $points = $readStat($row['stats'], 'PTS');
        $games = $readStat($row['stats'], 'GP');

        return is_numeric($points) && is_numeric($games) && (float)$games > 0;
    }

    return is_numeric($readStat($row['stats'], $column['key']));
};

$formatNumber = static function ($value, int $decimals = 0): string {
    return is_numeric($value)
        ? number_format((float)$value, $decimals, '.', '')
        : '—';
};

$formatBasicValue = static function (array $row, array $column) use ($readStat, $formatNumber): string {
    if (isset($column['pair'])) {
        [$madeKey, $attemptedKey] = $column['pair'];
        $made = $readStat($row['stats'], $madeKey);
        $attempted = $readStat($row['stats'], $attemptedKey);
        if ($column['format'] === 'percent') {
            if (!is_numeric($made) || !is_numeric($attempted) || (float)$attempted <= 0) {
                return '—';
            }

            return number_format((float)$made / (float)$attempted * 100, 1, '.', '') . '%';
        }

        if (!is_numeric($made) && !is_numeric($attempted)) {
            return '—';
        }

        return $formatNumber($made) . '-' . $formatNumber($attempted);
    }
    if ($column['format'] === 'ppg') {
        $points = $readStat($row['stats'], 'PTS');
        $games = $readStat($row['stats'], 'GP');
        if (!is_numeric($points) || !is_numeric($games) || (float)$games <= 0) {
            return '—';
        }

        return number_format((float)$points / (float)$games, 1, '.', '');
    }

    return $formatNumber(
        $readStat($row['stats'], $column['key']),
        $column['format'] === 'minutes' ? 2 : 0,
    );
};

$readAdvancedValue = static function (array $row, array $column) use ($readStat): mixed {
    if (($column['source'] ?? null) === 'stats') {
        return $readStat($row['stats'], $column['key']);
    }

    return $row['advanced'][$column['key']] ?? null;
};

$formatAdvancedValue = static function (array $row, array $column) use ($readAdvancedValue, $formatNumber): string {
    $value = $readAdvancedValue($row, $column);
    if (!is_numeric($value)) {
        return '—';
    }

    return match ($column['format']) {
        'percent' => number_format((float)$value * 100, 1, '.', '') . '%',
        'ratio' => number_format((float)$value, 3, '.', ''),
        'decimal' => number_format((float)$value, 1, '.', ''),
        'minutes' => $formatNumber($value, 2),
        default => $formatNumber($value),
    };
};

$basicRows = [];
foreach ($seasonRows as $row) {
    foreach ($basicColumns as $column) {
        if ($hasBasicValue($row, $column)) {
            $basicRows[] = $row;
            break;
        }
    }
}
$visibleBasicColumns = [];
foreach ($basicColumns as $column) {
    foreach ($basicRows as $row) {
        if ($hasBasicValue($row, $column)) {
            $visibleBasicColumns[] = $column;
            break;
        }
    }
}

$advancedMetricColumns = array_values(array_filter(
    $advancedColumns,
    static fn(array $column): bool => ($column['source'] ?? null) !== 'stats',
));
$advancedRows = [];
foreach ($seasonRows as $row) {
    foreach ($advancedMetricColumns as $column) {
        if (is_numeric($readAdvancedValue($row, $column))) {
            $advancedRows[] = $row;
            break;
        }
    }
}
$careerHasAdvancedMetrics = false;
foreach ($advancedMetricColumns as $column) {
    if (is_numeric($readAdvancedValue($careerRow, $column))) {
        $careerHasAdvancedMetrics = true;
        break;
    }
}
$advancedColumnRows = $advancedRows;
if ($careerHasAdvancedMetrics) {
    $advancedColumnRows[] = $careerRow;
}
$visibleAdvancedColumns = [];
foreach ($advancedColumns as $column) {
    foreach ($advancedColumnRows as $row) {
        if (is_numeric($readAdvancedValue($row, $column))) {
            $visibleAdvancedColumns[] = $column;
            break;
        }
    }
}

$tabs = [];
if ($basicRows !== [] && $visibleBasicColumns !== []) {
    $tabs[] = [
        'id' => 'basic',
        'label' => 'Basic Box Score Stats',
        'active' => true,
    ];
}
if ($visibleAdvancedColumns !== [] && ($careerHasAdvancedMetrics || $advancedRows !== [])) {
    $tabs[] = [
        'id' => 'advanced',
        'label' => 'Advanced Box Score Stats',
        'active' => $tabs === [],
    ];
}
$activeTabId = $tabs[0]['id'] ?? null;
?>

<?php if ($tabs === []) : ?>
    <p class="text-muted mb-0">No stats available.</p>
<?php else : ?>
    <div class="season-stats-tabs career-stats-tabs">
        <ul class="nav nav-tabs season-stats-tabs__nav" role="tablist">
            <?php foreach ($tabs as $tab) : ?>
                <?php $active = $tab['id'] === $activeTabId; ?>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link<?= $active ? ' active' : '' ?>"
                        id="basketball-career-<?= h($tab['id']) ?>-tab"
                        type="button"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#basketball-career-<?= h($tab['id']) ?>-panel"
                        aria-controls="basketball-career-<?= h($tab['id']) ?>-panel"
                        aria-selected="<?= $active ? 'true' : 'false' ?>"
                    ><?= h($tab['label']) ?></button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content mt-3 season-stats-tabs__panels">
            <?php foreach ($tabs as $tab) : ?>
                <?php $active = $tab['id'] === $activeTabId; ?>
                <div
                    class="tab-pane fade<?= $active ? ' show active' : '' ?>"
                    id="basketball-career-<?= h($tab['id']) ?>-panel"
                    role="tabpanel"
                    aria-labelledby="basketball-career-<?= h($tab['id']) ?>-tab"
                >
                    <div class="table-responsive">
                        <?php if ($tab['id'] === 'basic') : ?>
                            <table id="basketball-career-basic-stats-table" class="table table-striped table-bordered table-sm small">
                                <thead>
                                    <tr>
                                        <th>Season</th>
                                        <?php foreach ($visibleBasicColumns as $column) : ?>
                                            <th><?= h($column['label']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($basicRows as $row) : ?>
                                        <tr>
                                            <td class="fw-semibold"><?= h($row['label']) ?></td>
                                            <?php foreach ($visibleBasicColumns as $column) : ?>
                                                <td><?= h($formatBasicValue($row, $column)) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td><?= h($careerRow['label']) ?></td>
                                        <?php foreach ($visibleBasicColumns as $column) : ?>
                                            <td><?= h($formatBasicValue($careerRow, $column)) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php else : ?>
                            <table id="basketball-career-advanced-stats-table" class="table table-striped table-bordered table-sm small">
                                <thead>
                                    <tr>
                                        <th>Season</th>
                                        <?php foreach ($visibleAdvancedColumns as $column) : ?>
                                            <th><?= h($column['label']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($advancedRows as $row) : ?>
                                        <tr>
                                            <td class="fw-semibold"><?= h($row['label']) ?></td>
                                            <?php foreach ($visibleAdvancedColumns as $column) : ?>
                                                <td><?= h($formatAdvancedValue($row, $column)) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if ($careerHasAdvancedMetrics) : ?>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td><?= h($careerRow['label']) ?></td>
                                            <?php foreach ($visibleAdvancedColumns as $column) : ?>
                                                <td><?= h($formatAdvancedValue($careerRow, $column)) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
