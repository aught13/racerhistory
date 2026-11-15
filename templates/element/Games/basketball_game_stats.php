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
    ?? $game->team_season->team->getDisplayName()
    ?? 'Team';
$opponentName = $game->opponent->getMascotDisplayName();

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

$formatPercentForStats = static function (array $stats, string $madeKey, string $attemptKey) use ($formatPercent, $hasStatValue): string {
    $made = $stats[$madeKey] ?? null;
    $attempt = $stats[$attemptKey] ?? null;
    if (!$hasStatValue($made) && !$hasStatValue($attempt)) {
        return '-';
    }

    return $formatPercent($made, $attempt);
};

$formatAdvancedRate = static function ($value): string {
    if (!is_numeric($value)) {
        return '-';
    }

    return number_format((float)$value * 100, 1, '.', '') . '%';
};

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
    [
        'label' => 'eFG%',
        'team' => $formatAdvancedRate($teamBoxStats['advanced_stats']['eFG%'] ?? null),
        'teamPct' => '',
        'opponent' => $formatAdvancedRate($opponentBoxStats['advanced_stats']['eFG%'] ?? null),
        'opponentPct' => '',
    ],
    [
        'label' => 'TOV%',
        'team' => $formatAdvancedRate($teamBoxStats['advanced_stats']['TOV%'] ?? null),
        'teamPct' => '',
        'opponent' => $formatAdvancedRate($opponentBoxStats['advanced_stats']['TOV%'] ?? null),
        'opponentPct' => '',
    ],
    [
        'label' => 'ORB%',
        'team' => $formatAdvancedRate($teamBoxStats['advanced_stats']['ORB%'] ?? null),
        'teamPct' => '',
        'opponent' => $formatAdvancedRate($opponentBoxStats['advanced_stats']['ORB%'] ?? null),
        'opponentPct' => '',
    ],
    [
        'label' => 'FT/FGA',
        'team' => $formatAdvancedRate($teamBoxStats['advanced_stats']['FT/FGA'] ?? null),
        'teamPct' => '',
        'opponent' => $formatAdvancedRate($opponentBoxStats['advanced_stats']['FT/FGA'] ?? null),
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


    <?php if (!empty($playerRows) || !empty($opponentRows)) : ?>
        <?= $this->element('Games/basketball_player_box_score_tabs', [
            'teamName' => $teamName,
            'opponentName' => $opponentName,
            'playerRows' => $playerRows,
            'opponentRows' => $opponentRows,
            'teamBoxStats' => $teamBoxStats ?? [],
            'opponentBoxStats' => $opponentBoxStats ?? [],
            'teamTeamStats' => $teamTeamStats ?? null,
            'opponentTeamStats' => $opponentTeamStats ?? null,
        ]) ?>
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
