<?php
declare(strict_types=1);

/**
 * Public Basketball Game Stats Element
 *
 * @var \App\Model\Entity\Game $game
 * @var array $teamBoxStats
 * @var array $opponentBoxStats
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var \Cake\Collection\CollectionInterface|null $opponentPlayerStats
 * @var object|null $teamTeamStats
 * @var object|null $opponentTeamStats
 * @var array $teamPeriodStats
 * @var array $opponentPeriodStats
 * @var bool $hasPeriodStats
 * @var array<string,string> $fieldLabels
 * @var \App\View\AppView $this
 */

$teamName = $game->team_season->team->team_nickname
    ?? $game->team_season->team->team_name
    ?? 'Team';
$opponentName = $game->opponent->opponent_name ?? 'Opponent';

$playerRows = $playerStats ? $playerStats->toArray() : [];
$opponentRows = $opponentPlayerStats ? $opponentPlayerStats->toArray() : [];

$formatPair = static function ($made, $attempted): string {
    $madeVal = is_numeric($made) ? (int)$made : 0;
    $attemptVal = is_numeric($attempted) ? (int)$attempted : 0;

    return sprintf('%d-%d', $madeVal, $attemptVal);
};

$formatPercent = static function ($made, $attempted): string {
    $madeVal = is_numeric($made) ? (float)$made : 0.0;
    $attemptVal = is_numeric($attempted) ? (float)$attempted : 0.0;
    if ($attemptVal <= 0) {
        return '-';
    }

    return number_format($madeVal / $attemptVal * 100, 1) . '%';
};

$hasStatValue = static function ($value): bool {
    return $value !== null && $value !== '';
};

$formatPairForStats = static function (array $stats, string $madeKey, string $attemptKey) use ($formatPair, $hasStatValue): string {
    $made = $stats[$madeKey] ?? null;
    $attempt = $stats[$attemptKey] ?? null;
    if (!$hasStatValue($made) && !$hasStatValue($attempt)) {
        return '-';
    }
    if (!$hasStatValue($attempt)) {
        return (string)$made;
    }

    return $formatPair($made, $attempt);
};

$formatPairForPlayer = static function (object $stat, string $madeKey, string $attemptKey) use ($formatPair): string {
    $made = $stat->{$madeKey} ?? null;
    $attempt = $stat->{$attemptKey} ?? null;
    if ($attempt === null || $attempt === '') {
        return $made !== null && $made !== '' ? (string)$made : '-';
    }

    return $formatPair($made, $attempt);
};

$formatPercentForStats = static function (array $stats, string $madeKey, string $attemptKey) use ($formatPercent, $hasStatValue): string {
    $made = $stats[$madeKey] ?? null;
    $attempt = $stats[$attemptKey] ?? null;
    if (!$hasStatValue($made) && !$hasStatValue($attempt)) {
        return '-';
    }

    return $formatPercent($made, $attempt);
};

$playerColumns = [
    [
        'id' => 'GS',
        'label' => 'GS',
        'value' => static function ($stat, ?string $position = null): string {
            $gs = (int)($stat->GS ?? 0);
            if ($gs <= 0) {
                return '';
            }

            return $position ?? 'GS';
        },
        'total' => static fn(): string => '',
    ],
    [
        'id' => 'MIN',
        'label' => 'MIN',
        'value' => static fn($stat): string => (string)($stat->MIN ?? ''),
        'total' => static fn($stats): string => (string)($stats['MIN'] ?? '-'),
    ],
    [
        'id' => 'FG',
        'label' => 'FG',
        'value' => static fn($stat): string => $formatPairForPlayer($stat, 'FGM', 'FGA'),
        'total' => static fn($stats): string => $formatPair($stats['FGM'] ?? null, $stats['FGA'] ?? null),
    ],
    [
        'id' => 'FGP',
        'label' => 'FG%',
        'value' => static fn($stat): string => $formatPercent($stat->FGM ?? null, $stat->FGA ?? null),
        'total' => static fn($stats): string => $formatPercent($stats['FGM'] ?? null, $stats['FGA'] ?? null),
    ],
    [
        'id' => 'TP',
        'label' => '3PT',
        'value' => static fn($stat): string => $formatPairForPlayer($stat, 'TPM', 'TPA'),
        'total' => static fn($stats): string => $formatPair($stats['TPM'] ?? null, $stats['TPA'] ?? null),
    ],
    [
        'id' => 'TPP',
        'label' => '3PT%',
        'value' => static fn($stat): string => $formatPercent($stat->TPM ?? null, $stat->TPA ?? null),
        'total' => static fn($stats): string => $formatPercent($stats['TPM'] ?? null, $stats['TPA'] ?? null),
    ],
    [
        'id' => 'FT',
        'label' => 'FT',
        'value' => static fn($stat): string => $formatPairForPlayer($stat, 'FTM', 'FTA'),
        'total' => static fn($stats): string => $formatPair($stats['FTM'] ?? null, $stats['FTA'] ?? null),
    ],
    [
        'id' => 'FTP',
        'label' => 'FT%',
        'value' => static fn($stat): string => $formatPercent($stat->FTM ?? null, $stat->FTA ?? null),
        'total' => static fn($stats): string => $formatPercent($stats['FTM'] ?? null, $stats['FTA'] ?? null),
    ],
    [
        'id' => 'ORB',
        'label' => 'ORB',
        'value' => static fn($stat): string => (string)($stat->ORB ?? ''),
        'total' => static fn($stats): string => (string)($stats['ORB'] ?? '-'),
    ],
    [
        'id' => 'DRB',
        'label' => 'DRB',
        'value' => static fn($stat): string => (string)($stat->DRB ?? ''),
        'total' => static fn($stats): string => (string)($stats['DRB'] ?? '-'),
    ],
    [
        'id' => 'RB',
        'label' => 'RB',
        'value' => static fn($stat): string => (string)($stat->RB ?? ''),
        'total' => static fn($stats): string => (string)($stats['RB'] ?? '-'),
    ],
    [
        'id' => 'AST',
        'label' => 'AST',
        'value' => static fn($stat): string => (string)($stat->AST ?? ''),
        'total' => static fn($stats): string => (string)($stats['AST'] ?? '-'),
    ],
    [
        'id' => 'STL',
        'label' => 'STL',
        'value' => static fn($stat): string => (string)($stat->STL ?? ''),
        'total' => static fn($stats): string => (string)($stats['STL'] ?? '-'),
    ],
    [
        'id' => 'BS',
        'label' => 'BLK',
        'value' => static fn($stat): string => (string)($stat->BS ?? ''),
        'total' => static fn($stats): string => (string)($stats['BS'] ?? '-'),
    ],
    [
        'id' => 'TRN',
        'label' => 'TO',
        'value' => static fn($stat): string => (string)($stat->TRN ?? ''),
        'total' => static fn($stats): string => (string)($stats['TRN'] ?? '-'),
    ],
    [
        'id' => 'PF',
        'label' => 'PF',
        'value' => static fn($stat): string => (string)($stat->PF ?? ''),
        'total' => static fn($stats): string => (string)($stats['PF'] ?? '-'),
    ],
    [
        'id' => 'PTS',
        'label' => 'PTS',
        'value' => static fn($stat): string => (string)($stat->PTS ?? ''),
        'total' => static fn($stats): string => (string)($stats['PTS'] ?? '-'),
    ],
];

$hasPlayerStatValue = static function (array $rows, string $columnId): bool {
    foreach ($rows as $stat) {
        switch ($columnId) {
            case 'GS':
                if (!empty($stat->GS)) {
                    return true;
                }
                break;
            case 'FG':
                if (!empty($stat->FGM) || !empty($stat->FGA)) {
                    return true;
                }
                break;
            case 'FGP':
                if (!empty($stat->FGA)) {
                    return true;
                }
                break;
            case 'TP':
                if (!empty($stat->TPM) || !empty($stat->TPA)) {
                    return true;
                }
                break;
            case 'TPP':
                if (!empty($stat->TPA)) {
                    return true;
                }
                break;
            case 'FT':
                if (!empty($stat->FTM) || !empty($stat->FTA)) {
                    return true;
                }
                break;
            case 'FTP':
                if (!empty($stat->FTA)) {
                    return true;
                }
                break;
            default:
                if (!empty($stat->{$columnId})) {
                    return true;
                }
                break;
        }
    }

    return false;
};

$visibleTeamPlayerColumns = array_values(array_filter(
    $playerColumns,
    static fn($column) => $hasPlayerStatValue($playerRows, $column['id']),
));

$visibleOpponentPlayerColumns = array_values(array_filter(
    $playerColumns,
    static fn($column) => $hasPlayerStatValue($opponentRows, $column['id']),
));

$hasAnyStats = !empty($playerRows) || !empty($opponentRows) || !empty($teamBoxStats) || !empty($opponentBoxStats);

$summaryRows = [
    [
        'label' => 'Field Goals',
        'team' => $formatPairForStats($teamBoxStats ?? [], 'FGM', 'FGA'),
        'teamPct' => $formatPercentForStats($teamBoxStats ?? [], 'FGM', 'FGA'),
        'opponent' => $formatPairForStats($opponentBoxStats ?? [], 'FGM', 'FGA'),
        'opponentPct' => $formatPercentForStats($opponentBoxStats ?? [], 'FGM', 'FGA'),
    ],
    [
        'label' => '3PT',
        'team' => $formatPairForStats($teamBoxStats ?? [], 'TPM', 'TPA'),
        'teamPct' => $formatPercentForStats($teamBoxStats ?? [], 'TPM', 'TPA'),
        'opponent' => $formatPairForStats($opponentBoxStats ?? [], 'TPM', 'TPA'),
        'opponentPct' => $formatPercentForStats($opponentBoxStats ?? [], 'TPM', 'TPA'),
    ],
    [
        'label' => 'Free Throws',
        'team' => $formatPairForStats($teamBoxStats ?? [], 'FTM', 'FTA'),
        'teamPct' => $formatPercentForStats($teamBoxStats ?? [], 'FTM', 'FTA'),
        'opponent' => $formatPairForStats($opponentBoxStats ?? [], 'FTM', 'FTA'),
        'opponentPct' => $formatPercentForStats($opponentBoxStats ?? [], 'FTM', 'FTA'),
    ],
    [
        'label' => 'Rebounds',
        'team' => $hasStatValue($teamBoxStats['RB'] ?? null) ? $teamBoxStats['RB'] : '-',
        'teamPct' => '',
        'opponent' => $hasStatValue($opponentBoxStats['RB'] ?? null) ? $opponentBoxStats['RB'] : '-',
        'opponentPct' => '',
    ],
    [
        'label' => 'Assists',
        'team' => $hasStatValue($teamBoxStats['AST'] ?? null) ? $teamBoxStats['AST'] : '-',
        'teamPct' => '',
        'opponent' => $hasStatValue($opponentBoxStats['AST'] ?? null) ? $opponentBoxStats['AST'] : '-',
        'opponentPct' => '',
    ],
    [
        'label' => 'Turnovers',
        'team' => $hasStatValue($teamBoxStats['TRN'] ?? null) ? $teamBoxStats['TRN'] : '-',
        'teamPct' => '',
        'opponent' => $hasStatValue($opponentBoxStats['TRN'] ?? null) ? $opponentBoxStats['TRN'] : '-',
        'opponentPct' => '',
    ],
];

$miscRows = [
    ['label' => 'Technical Fouls', 'key' => 'TF'],
    ['label' => 'Second Chance Points', 'key' => 'SND'],
    ['label' => 'Scores Tied', 'key' => 'TIED'],
    ['label' => 'Points in the Paint', 'key' => 'PNT'],
    ['label' => 'Fast Break Points', 'key' => 'FB'],
    ['label' => 'Lead Changes', 'key' => 'LC'],
    ['label' => 'Points off Turnovers', 'key' => 'OTO'],
    ['label' => 'Bench Points', 'key' => 'BN'],
];

$periodLabels = [];
$periodIds = array_unique(array_merge(array_keys($teamPeriodStats ?? []), array_keys($opponentPeriodStats ?? [])));
sort($periodIds);
foreach ($periodIds as $periodId) {
    $periodLabels[] = [
        'id' => (string)$periodId,
        'label' => ctype_digit((string)$periodId) ? 'P' . $periodId : strtoupper((string)$periodId),
    ];
}

$filteredSummaryRows = [];
foreach ($summaryRows as $row) {
    if (($row['team'] !== '-' && $row['team'] !== '') || ($row['opponent'] !== '-' && $row['opponent'] !== '') || ($row['teamPct'] !== '-' && $row['teamPct'] !== '') || ($row['opponentPct'] !== '-' && $row['opponentPct'] !== '')) {
        $filteredSummaryRows[] = $row;
    }
}

$filteredMiscRows = [];
foreach ($miscRows as $row) {
    $teamValue = $teamBoxStats[$row['key']] ?? null;
    $opponentValue = $opponentBoxStats[$row['key']] ?? null;
    if ($hasStatValue($teamValue) || $hasStatValue($opponentValue)) {
        $filteredMiscRows[] = $row;
    }
}

$hasShootingByPeriod = false;
foreach ($periodLabels as $periodInfo) {
    $periodId = $periodInfo['id'];
    $teamPeriod = $teamPeriodStats[$periodId] ?? [];
    $oppPeriod = $opponentPeriodStats[$periodId] ?? [];
    foreach (['FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA'] as $key) {
        if ($hasStatValue($teamPeriod[$key] ?? null) || $hasStatValue($oppPeriod[$key] ?? null)) {
            $hasShootingByPeriod = true;
            break 2;
        }
    }
}
?>

<?php if (!$hasAnyStats) : ?>
    <div class="alert alert-info mb-0">Game stats are not available yet.</div>
<?php else : ?>
    <?php if (!empty($filteredSummaryRows)) : ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-borderless game-summary-table" id="game-team-summary-table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-end"><?= h($teamName) ?></th>
                        <th class="text-end"><?= h($opponentName) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredSummaryRows as $row) : ?>
                        <tr>
                            <th scope="row" class="text-muted"><?= h($row['label']) ?></th>
                            <td class="text-end">
                                <?= h($row['team']) ?>
                                <?php if (!empty($row['teamPct'])) : ?>
                                    <span class="text-muted small">(<?= h($row['teamPct']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?= h($row['opponent']) ?>
                                <?php if (!empty($row['opponentPct'])) : ?>
                                    <span class="text-muted small">(<?= h($row['opponentPct']) ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>


    <?php if (!empty($playerRows)) : ?>
        <div class="game-stats-block mb-4">
            <h3 class="h5 mb-3"><?= h($teamName) ?> Player Stats</h3>
            <div class="table-responsive">
                <table class="table table-striped table-sm game-player-table js-datatable" id="game-team-stats-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <?php foreach ($visibleTeamPlayerColumns as $column) : ?>
                                <th><?= h($column['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($playerRows as $stat) : ?>
                            <?php
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
                            $personId = $person->id ?? 0;
                            $position = $stat->team_season_roster->roster_position ?? null;
                            ?>
                            <tr>
                                <td><?= h($stat->team_season_roster->roster_number ?? '') ?></td>
                                <td>
                                    <?php if ($personId) : ?>
                                        <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $personId]) ?>" class="game-player-link">
                                            <?= h($name) ?>
                                        </a>
                                    <?php else : ?>
                                        <?= h($name) ?>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($visibleTeamPlayerColumns as $column) : ?>
                                    <td><?= h($column['value']($stat, $position)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($teamBoxStats)) : ?>
                        <tfoot>
                            <?php if (!empty($teamTeamStats)) : ?>
                                <tr class="table-light">
                                    <td colspan="2">TEAM</td>
                                    <?php foreach ($visibleTeamPlayerColumns as $column) : ?>
                                        <?php
                                        $value = '-';
                                        switch ($column['id']) {
                                            case 'ORB':
                                            case 'DRB':
                                            case 'RB':
                                            case 'TRN':
                                            case 'PTS':
                                                $value = $teamTeamStats->{$column['id']} ?? '-';
                                                break;
                                            default:
                                                $value = '-';
                                                break;
                                        }
                                        ?>
                                        <td><?= h($value) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2">TEAM TOTALS</td>
                                <?php foreach ($visibleTeamPlayerColumns as $column) : ?>
                                    <td><?= h($column['total']($teamBoxStats)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($opponentRows)) : ?>
        <div class="game-stats-block">
            <h3 class="h5 mb-3"><?= h($opponentName) ?> Player Stats</h3>
            <div class="table-responsive">
                <table class="table table-striped table-sm game-player-table js-datatable" id="game-opponent-stats-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <?php foreach ($visibleOpponentPlayerColumns as $column) : ?>
                                <th><?= h($column['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($opponentRows as $stat) : ?>
                            <?php $position = $stat->position ?? null; ?>
                            <tr>
                                <td><?= h($stat->jersey ?? '') ?></td>
                                <td><?= h($stat->name ?? '') ?></td>
                                <?php foreach ($visibleOpponentPlayerColumns as $column) : ?>
                                    <td><?= h($column['value']($stat, $position)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($opponentBoxStats)) : ?>
                        <tfoot>
                            <?php if (!empty($opponentTeamStats)) : ?>
                                <tr class="table-light">
                                    <td colspan="2">TEAM</td>
                                    <?php foreach ($visibleOpponentPlayerColumns as $column) : ?>
                                        <?php
                                        $value = '-';
                                        switch ($column['id']) {
                                            case 'ORB':
                                            case 'DRB':
                                            case 'RB':
                                            case 'TRN':
                                            case 'PTS':
                                                $value = $opponentTeamStats->{$column['id']} ?? '-';
                                                break;
                                            default:
                                                $value = '-';
                                                break;
                                        }
                                        ?>
                                        <td><?= h($value) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2">OPPONENT TOTALS</td>
                                <?php foreach ($visibleOpponentPlayerColumns as $column) : ?>
                                    <td><?= h($column['total']($opponentBoxStats)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($filteredMiscRows)) : ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-borderless game-summary-table">
                <thead>
                    <tr>
                        <th>Team Summary</th>
                        <th class="text-end"><?= h($teamName) ?></th>
                        <th class="text-end"><?= h($opponentName) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredMiscRows as $row) : ?>
                        <tr>
                            <th scope="row" class="text-muted"><?= h($row['label']) ?></th>
                            <td class="text-end"><?= h($teamBoxStats[$row['key']] ?? '-') ?></td>
                            <td class="text-end"><?= h($opponentBoxStats[$row['key']] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($hasShootingByPeriod) : ?>
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-striped game-period-table">
                        <thead>
                            <tr>
                                <th colspan="4">Shooting by Period - <?= h($teamName) ?></th>
                            </tr>
                            <tr>
                                <th>Period</th>
                                <th class="text-end">FG</th>
                                <th class="text-end">3PT</th>
                                <th class="text-end">FT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periodLabels as $periodInfo) : ?>
                                <?php
                                $periodId = $periodInfo['id'];
                                $teamPeriod = $teamPeriodStats[$periodId] ?? [];
                                ?>
                                <tr>
                                    <th scope="row" class="text-muted"><?= h($periodInfo['label']) ?></th>
                                    <td class="text-end">
                                        <?= h($formatPair($teamPeriod['FGM'] ?? null, $teamPeriod['FGA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($teamPeriod['FGM'] ?? null, $teamPeriod['FGA'] ?? null)) ?>)</span>
                                    </td>
                                    <td class="text-end">
                                        <?= h($formatPair($teamPeriod['TPM'] ?? null, $teamPeriod['TPA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($teamPeriod['TPM'] ?? null, $teamPeriod['TPA'] ?? null)) ?>)</span>
                                    </td>
                                    <td class="text-end">
                                        <?= h($formatPair($teamPeriod['FTM'] ?? null, $teamPeriod['FTA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($teamPeriod['FTM'] ?? null, $teamPeriod['FTA'] ?? null)) ?>)</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-striped game-period-table">
                        <thead>
                            <tr>
                                <th colspan="4">Shooting by Period - <?= h($opponentName) ?></th>
                            </tr>
                            <tr>
                                <th>Period</th>
                                <th class="text-end">FG</th>
                                <th class="text-end">3PT</th>
                                <th class="text-end">FT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periodLabels as $periodInfo) : ?>
                                <?php
                                $periodId = $periodInfo['id'];
                                $oppPeriod = $opponentPeriodStats[$periodId] ?? [];
                                ?>
                                <tr>
                                    <th scope="row" class="text-muted"><?= h($periodInfo['label']) ?></th>
                                    <td class="text-end">
                                        <?= h($formatPair($oppPeriod['FGM'] ?? null, $oppPeriod['FGA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($oppPeriod['FGM'] ?? null, $oppPeriod['FGA'] ?? null)) ?>)</span>
                                    </td>
                                    <td class="text-end">
                                        <?= h($formatPair($oppPeriod['TPM'] ?? null, $oppPeriod['TPA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($oppPeriod['TPM'] ?? null, $oppPeriod['TPA'] ?? null)) ?>)</span>
                                    </td>
                                    <td class="text-end">
                                        <?= h($formatPair($oppPeriod['FTM'] ?? null, $oppPeriod['FTA'] ?? null)) ?>
                                        <span class="text-muted small">(<?= h($formatPercent($oppPeriod['FTM'] ?? null, $oppPeriod['FTA'] ?? null)) ?>)</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
