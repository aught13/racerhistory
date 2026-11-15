<?php
declare(strict_types=1);

/**
 * @var string $teamName
 * @var string $opponentName
 * @var array<int,object> $playerRows
 * @var array<int,object> $opponentRows
 * @var array<string,mixed> $teamBoxStats
 * @var array<string,mixed> $opponentBoxStats
 * @var object|null $teamTeamStats
 * @var object|null $opponentTeamStats
 * @var \App\View\AppView $this
 */

$formatBoxValue = static function ($value, string $format): string {
    if ($format === 'starter') {
        return is_numeric($value) && (float)$value > 0 ? '*' : '';
    }
    if (!is_numeric($value)) {
        return '-';
    }

    return match ($format) {
        'minutes' => number_format((float)$value, 2, '.', ''),
        'percent' => number_format((float)$value * 100, 1, '.', '') . '%',
        'ratio' => number_format((float)$value, 3, '.', ''),
        'decimal' => number_format((float)$value, 1, '.', ''),
        default => number_format((float)$value, 0, '.', ''),
    };
};

$formatPercent = static function ($made, $attempted) use ($formatBoxValue): string {
    if (!is_numeric($made) || !is_numeric($attempted) || (float)$attempted <= 0) {
        return '-';
    }

    return $formatBoxValue((float)$made / (float)$attempted, 'percent');
};

$basicColumns = [
    ['label' => 'GS', 'key' => 'GS', 'field' => 'GS', 'format' => 'starter'],
    ['label' => 'MP', 'key' => 'MIN', 'field' => 'MIN', 'format' => 'minutes'],
    ['label' => 'FG', 'key' => 'FGM', 'field' => 'FGM', 'format' => 'count'],
    ['label' => 'FGA', 'key' => 'FGA', 'field' => 'FGA', 'format' => 'count'],
    ['label' => 'FG%', 'key' => 'FG%', 'made' => 'FGM', 'attempted' => 'FGA', 'format' => 'percent'],
    ['label' => '2P', 'key' => '2PM', 'derive' => ['FGM', 'TPM'], 'format' => 'count'],
    ['label' => '2PA', 'key' => '2PA', 'derive' => ['FGA', 'TPA'], 'format' => 'count'],
    ['label' => '2P%', 'key' => '2P%', 'made' => '2PM', 'attempted' => '2PA', 'format' => 'percent'],
    ['label' => '3P', 'key' => 'TPM', 'field' => 'TPM', 'format' => 'count'],
    ['label' => '3PA', 'key' => 'TPA', 'field' => 'TPA', 'format' => 'count'],
    ['label' => '3P%', 'key' => '3P%', 'made' => 'TPM', 'attempted' => 'TPA', 'format' => 'percent'],
    ['label' => 'FT', 'key' => 'FTM', 'field' => 'FTM', 'format' => 'count'],
    ['label' => 'FTA', 'key' => 'FTA', 'field' => 'FTA', 'format' => 'count'],
    ['label' => 'FT%', 'key' => 'FT%', 'made' => 'FTM', 'attempted' => 'FTA', 'format' => 'percent'],
    ['label' => 'ORB', 'key' => 'ORB', 'field' => 'ORB', 'format' => 'count'],
    ['label' => 'DRB', 'key' => 'DRB', 'field' => 'DRB', 'format' => 'count'],
    ['label' => 'TRB', 'key' => 'RB', 'field' => 'RB', 'format' => 'count'],
    ['label' => 'AST', 'key' => 'AST', 'field' => 'AST', 'format' => 'count'],
    ['label' => 'STL', 'key' => 'STL', 'field' => 'STL', 'format' => 'count'],
    ['label' => 'BLK', 'key' => 'BS', 'field' => 'BS', 'format' => 'count'],
    ['label' => 'TOV', 'key' => 'TRN', 'field' => 'TRN', 'format' => 'count'],
    ['label' => 'PF', 'key' => 'PF', 'field' => 'PF', 'format' => 'count'],
    ['label' => 'PTS', 'key' => 'PTS', 'field' => 'PTS', 'format' => 'count'],
];

$advancedColumns = [
    ['label' => 'GS', 'key' => 'GS', 'field' => 'GS', 'format' => 'starter'],
    ['label' => 'MP', 'key' => 'MIN', 'field' => 'MIN', 'format' => 'minutes'],
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

$readPlayerValue = static function (object $stat, string $key, bool $advanced): mixed {
    if ($advanced && !in_array($key, ['GS', 'MIN'], true)) {
        $advancedStats = $stat->get('advanced_stats');

        return is_array($advancedStats) ? ($advancedStats[$key] ?? null) : null;
    }

    return $stat->{$key} ?? null;
};

$formatTotalValue = static function (array $stats, array $column) use ($formatBoxValue, $formatPercent): string {
    if (isset($column['made'], $column['attempted'])) {
        $made = $column['made'] === '2PM'
            ? (is_numeric($stats['FGM'] ?? null) && is_numeric($stats['TPM'] ?? null)
                ? (float)$stats['FGM'] - (float)$stats['TPM']
                : null)
            : ($stats[$column['made']] ?? null);
        $attempted = $column['attempted'] === '2PA'
            ? (is_numeric($stats['FGA'] ?? null) && is_numeric($stats['TPA'] ?? null)
                ? (float)$stats['FGA'] - (float)$stats['TPA']
                : null)
            : ($stats[$column['attempted']] ?? null);

        return $formatPercent($made, $attempted);
    }

    if (isset($column['derive'])) {
        [$madeKey, $threePointKey] = $column['derive'];
        if (!is_numeric($stats[$madeKey] ?? null) || !is_numeric($stats[$threePointKey] ?? null)) {
            return '-';
        }

        return $formatBoxValue(
            max((float)$stats[$madeKey] - (float)$stats[$threePointKey], 0),
            $column['format'],
        );
    }

    return $formatBoxValue(
        $stats[$column['field'] ?? $column['key']] ?? null,
        $column['format'],
    );
};

$formatTeamValue = static function (object $stats, array $column) use ($formatBoxValue): string {
    $field = match ($column['key']) {
        'TRB' => 'RB',
        'TOV' => 'TRN',
        'BLK' => 'BS',
        default => $column['field'] ?? null,
    };

    return $field !== null
        ? $formatBoxValue($stats->{$field} ?? null, $column['format'])
        : '-';
};

$groups = [
    [
        'key' => 'team',
        'title' => $teamName,
        'rows' => $playerRows,
        'boxStats' => $teamBoxStats,
        'teamStats' => $teamTeamStats,
        'opponent' => false,
        'totalsLabel' => 'TEAM TOTALS',
    ],
    [
        'key' => 'opponent',
        'title' => $opponentName,
        'rows' => $opponentRows,
        'boxStats' => $opponentBoxStats,
        'teamStats' => $opponentTeamStats,
        'opponent' => true,
        'totalsLabel' => 'OPPONENT TOTALS',
    ],
];

$tabs = [
    ['id' => 'basic', 'label' => 'Basic Box Score Stats', 'columns' => $basicColumns, 'advanced' => false],
    ['id' => 'advanced', 'label' => 'Advanced Box Score Stats', 'columns' => $advancedColumns, 'advanced' => true],
];
?>

<div class="game-box-score-tabs" data-game-box-score-tabs>
    <ul class="nav nav-tabs mb-3" role="tablist">
        <?php foreach ($tabs as $index => $tab) : ?>
            <?php $tabId = 'game-box-score-' . $tab['id'] . '-tab'; ?>
            <?php $panelId = 'game-box-score-' . $tab['id'] . '-panel'; ?>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link<?= $index === 0 ? ' active' : '' ?>"
                    id="<?= h($tabId) ?>"
                    type="button"
                    role="tab"
                    data-game-stats-tab="<?= h($tab['id']) ?>"
                    aria-controls="<?= h($panelId) ?>"
                    aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                ><?= h($tab['label']) ?></button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <?php foreach ($tabs as $index => $tab) : ?>
            <?php $panelId = 'game-box-score-' . $tab['id'] . '-panel'; ?>
            <div
                class="tab-pane fade<?= $index === 0 ? ' show active' : ' d-none' ?>"
                id="<?= h($panelId) ?>"
                role="tabpanel"
                aria-labelledby="game-box-score-<?= h($tab['id']) ?>-tab"
                data-game-stats-panel="<?= h($tab['id']) ?>"
            >
                <?php foreach ($groups as $group) : ?>
                    <?php if (empty($group['rows'])) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div class="game-stats-block mb-4">
                        <h3 class="h5 mb-3"><?= h($group['title']) ?> Player Stats</h3>
                        <div class="table-responsive">
                            <table
                                class="table table-striped table-sm small game-player-table js-datatable"
                                id="<?= h('game-' . $group['key'] . ($tab['advanced'] ? '-advanced' : '') . '-stats-table') ?>"
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
                                    <?php foreach ($group['rows'] as $stat) : ?>
                                        <?php
                                        $person = $stat->team_season_roster->person ?? null;
                                        $name = $group['opponent']
                                            ? trim((string)($stat->name ?? ''))
                                            : (string)($person->display
                                                ?? $person->full
                                                ?? trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? '')));
                                        if ($name === '') {
                                            $name = 'Unknown';
                                        }
                                        $personId = $group['opponent'] ? 0 : (int)($person->id ?? 0);
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($personId > 0) : ?>
                                                    <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $personId]) ?>" class="game-player-link">
                                                        <?= h($name) ?>
                                                    </a>
                                                <?php else : ?>
                                                    <?= h($name) ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($tab['columns'] as $column) : ?>
                                                <?php
                                                $value = $readPlayerValue(
                                                    $stat,
                                                    $column['key'],
                                                    $tab['advanced'] || isset($column['made']) || isset($column['derive']),
                                                );
                                                $orderValue = $column['key'] === 'GS' && is_numeric($value)
                                                    ? (int)$value
                                                    : null;
                                                ?>
                                                <td<?= $orderValue !== null ? ' data-order="' . h((string)$orderValue) . '"' : '' ?>>
                                                    <?= h($formatBoxValue($value, $column['format'])) ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (!$tab['advanced'] && !empty($group['boxStats'])) : ?>
                                    <tfoot>
                                        <?php if (!empty($group['teamStats'])) : ?>
                                            <tr class="table-light">
                                                <td>TEAM</td>
                                                <?php foreach ($tab['columns'] as $column) : ?>
                                                    <td><?= h($formatTeamValue($group['teamStats'], $column)) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="table-secondary fw-bold">
                                            <td><?= h($group['totalsLabel']) ?></td>
                                            <?php foreach ($tab['columns'] as $column) : ?>
                                                <td><?= h($formatTotalValue($group['boxStats'], $column)) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($index === 0) : ?>
                    <p class="text-muted small mb-0">* = game starter.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
