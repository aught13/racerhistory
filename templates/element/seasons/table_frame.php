<?php
/**
 * @var array<int,\App\Model\Entity\TeamSeason> $teamSeasons
 * @var array<int,array<string,array<string,mixed>>> $recordSummaries
 * @var string|null $mode
 * @var string|null $teamFilter
 * @var \App\View\AppView $this
 */
$mode = $mode ?? 'standard';
$teamFilter = $teamFilter ?? 'all';
$isSplits = $mode === 'splits';

$recordGroups = ['Overall', 'Conference', 'Conference Tournament', 'Postseason'];
$formatSplitRecord = static function (array $record, bool $showTies): array {
    $wins = $record['W'] ?? null;
    $losses = $record['L'] ?? null;
    $ties = $record['T'] ?? null;
    $total = 0;
    if ($wins !== null || $losses !== null || $ties !== null) {
        $total = (int)($wins ?? 0) + (int)($losses ?? 0) + (int)($ties ?? 0);
    }

    if ($total === 0) {
        return $showTies ? ['-', '-', '-'] : ['-', '-'];
    }

    $cells = [
        $wins !== null ? (string)$wins : '-',
        $losses !== null ? (string)$losses : '-',
    ];

    if ($showTies) {
        $cells[] = $ties !== null ? (string)$ties : '-';
    }

    return $cells;
};

$recordHasTies = static function (array $record): bool {
    return isset($record['T']) && (int)$record['T'] > 0;
};

$seasonSplitRows = [];
$hasTies = false;
if (!empty($teamSeasons)) {
    foreach ($teamSeasons as $index => $teamSeason) {
        $seasonStart = $teamSeason->season->start ?? '';
        $seasonEnd = $teamSeason->season->end ?? '';
        $seasonLabel = $seasonStart !== '' && $seasonEnd !== ''
            ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
            : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
        $teamLabel = $teamSeason->team->team_name ?? 'Team';
        $primarySummary = $recordSummaries[$teamSeason->id] ?? [];
        $overallSplits = $primarySummary['Overall']['splits'] ?? [];
        $conferenceSplits = $primarySummary['Conference']['splits'] ?? [];
        $overallHome = $overallSplits['Home'] ?? [];
        $overallRoad = $overallSplits['Road'] ?? [];
        $overallNeutral = $overallSplits['Neutral'] ?? [];
        $conferenceHome = $conferenceSplits['Home'] ?? [];
        $conferenceRoad = $conferenceSplits['Road'] ?? [];
        $confTourn = $primarySummary['Conference Tournament']['totals'] ?? [];
        $postseason = $primarySummary['Postseason']['totals'] ?? [];

        $hasTies = $hasTies
            || $recordHasTies($overallHome)
            || $recordHasTies($overallRoad)
            || $recordHasTies($overallNeutral)
            || $recordHasTies($conferenceHome)
            || $recordHasTies($conferenceRoad)
            || $recordHasTies($confTourn)
            || $recordHasTies($postseason);

        $seasonSplitRows[] = [
            'index' => $index + 1,
            'team' => $teamLabel,
            'seasonLabel' => $seasonLabel,
            'overall_home_record' => $overallHome,
            'overall_road_record' => $overallRoad,
            'overall_neutral_record' => $overallNeutral,
            'conference_home_record' => $conferenceHome,
            'conference_road_record' => $conferenceRoad,
            'conf_tourn_record' => $confTourn,
            'postseason_record' => $postseason,
            'post_label' => $primarySummary['Postseason']['label'] ?? null,
        ];
    }
}

if (!empty($seasonSplitRows)) {
    foreach ($seasonSplitRows as &$splitRow) {
        $splitRow['overall_home_cells'] = $formatSplitRecord($splitRow['overall_home_record'], $hasTies);
        $splitRow['overall_road_cells'] = $formatSplitRecord($splitRow['overall_road_record'], $hasTies);
        $splitRow['overall_neutral_cells'] = $formatSplitRecord($splitRow['overall_neutral_record'], $hasTies);
        $splitRow['conference_home_cells'] = $formatSplitRecord($splitRow['conference_home_record'], $hasTies);
        $splitRow['conference_road_cells'] = $formatSplitRecord($splitRow['conference_road_record'], $hasTies);
        $splitRow['conf_tourn_cells'] = $formatSplitRecord($splitRow['conf_tourn_record'], $hasTies);
        $splitRow['postseason_cells'] = $formatSplitRecord($splitRow['postseason_record'], $hasTies);
        if ($splitRow['post_label'] === null || $splitRow['post_label'] === '') {
            $splitRow['post_label'] = '-';
        }
    }
    unset($splitRow);
}

$hasSplits = !empty($seasonSplitRows);
$toggleUrl = $isSplits
    ? $this->Url->build(['controller' => 'Seasons', 'action' => 'index', '?' => ['team' => $teamFilter]])
    : $this->Url->build(['controller' => 'Seasons', 'action' => 'splits', '?' => ['team' => $teamFilter]]);
$toggleLabel = $isSplits ? 'Show Standard' : 'Show Splits';
$heading = $isSplits ? 'Season Splits' : 'Team Seasons';
$subheading = $isSplits ? 'Home, road, and neutral splits for each season.' : '';
?>
<turbo-frame id="seasons-table-frame"
             data-seasons-view="<?= h($isSplits ? 'splits' : 'standard') ?>"
             data-splits-has-ties="<?= $hasTies ? 'true' : 'false' ?>">
    <div class="container py-4">
        <?php if (empty($teamSeasons)) : ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No seasons available yet.
            </div>
        <?php else : ?>
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3">
                <div>
                    <h1 class="h3 mb-1"><?= h($heading) ?></h1>
                    <?php if ($subheading !== '') : ?>
                        <p class="text-muted small mb-0"><?= h($subheading) ?></p>
                    <?php endif; ?>
                </div>
                <div id="seasons-controls" class="d-flex align-items-center gap-2">
                    <?php if ($hasSplits) : ?>
                        <a href="<?= h($toggleUrl) ?>" data-turbo-frame="seasons-table-frame" class="btn btn-sm btn-outline-secondary ms-1">
                            <?= h($toggleLabel) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div id="searchbuilder-panel" class="searchbuilder-panel"></div>

            <div class="seasons-table-card shadow-sm">
                <?php if ($isSplits) : ?>
                        <?php $splitColspan = $hasTies ? 3 : 2; ?>
                        <table id="season-splits-table" class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th rowspan="2">#</th>
                                    <th rowspan="2">Team</th>
                                    <th rowspan="2">Season</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Home</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Road</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Neutral</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Conf Home</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Conf Road</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Conf Tourn</th>
                                    <th colspan="<?= $splitColspan ?>" class="text-center">Postseason</th>
                                    <th rowspan="2">Type</th>
                                </tr>
                                <tr>
                                    <?php for ($group = 0; $group < 7; $group++) : ?>
                                        <th class="text-end">W</th>
                                        <th class="text-end">L</th>
                                        <?php if ($hasTies) : ?>
                                            <th class="text-end">T</th>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seasonSplitRows as $splitRow) : ?>
                                    <tr>
                                        <td class="text-muted seasons-row-number"><?= $splitRow['index'] ?></td>
                                        <td><?= h($splitRow['team']) ?></td>
                                        <td><?= h($splitRow['seasonLabel']) ?></td>
                                        <?php foreach ($splitRow['overall_home_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['overall_road_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['overall_neutral_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['conference_home_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['conference_road_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['conf_tourn_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($splitRow['postseason_cells'] as $cell) : ?>
                                            <td class="text-end"><?= h($cell) ?></td>
                                        <?php endforeach; ?>
                                        <td><?= h($splitRow['post_label']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php else : ?>
                        <table id="seasons-table" class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th rowspan="2">#</th>
                                    <th rowspan="2">Team</th>
                                    <th rowspan="2">Season</th>
                                    <th rowspan="2">Conf</th>
                                    <th rowspan="2">Conf finish</th>
                                    <th colspan="3" class="text-center">Overall</th>
                                    <th colspan="3" class="text-center">Conference</th>
                                    <th colspan="3" class="text-center">Conference Tourn</th>
                                    <th colspan="3" class="text-center">Postseason</th>
                                </tr>
                                <tr>
                                    <th class="text-end">W</th>
                                    <th class="text-end">L</th>
                                    <th class="text-end">Pct</th>
                                    <th class="text-end">W</th>
                                    <th class="text-end">L</th>
                                    <th class="text-end">Pct</th>
                                    <th class="text-end">W</th>
                                    <th class="text-end">L</th>
                                    <th class="text-end">Pct</th>
                                    <th class="text-end">W</th>
                                    <th class="text-end">L</th>
                                    <th class="text-end">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamSeasons as $index => $teamSeason) :
                                    $seasonStart = $teamSeason->season->start ?? '';
                                    $seasonEnd = $teamSeason->season->end ?? '';
                                    $seasonLabel = $seasonStart !== '' && $seasonEnd !== ''
                                        ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
                                        : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
                                    $teamLabel = $teamSeason->team->team_name ?? 'Team';
                                    $primarySummary = $recordSummaries[$teamSeason->id] ?? [];
                                    ?>
                                    <tr>
                                        <td class="text-muted seasons-row-number"><?= $index + 1 ?></td>
                                        <td><?= h($teamLabel) ?></td>
                                        <td data-order="<?= h($seasonStart) ?>">
                                            <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $teamSeason->id]) ?>"
                                               class="text-decoration-none"
                                               data-turbo-frame="_top">
                                                <?= h($seasonLabel) ?>
                                            </a>
                                        </td>
                                        <td><?= h($teamSeason->league_abbr ?: $teamSeason->league ?: '-') ?></td>
                                        <td><?= h($teamSeason->league_finish ?: '-') ?></td>
                                        <?php foreach ($recordGroups as $typeKey) :
                                            $record = $primarySummary[$typeKey]['totals'] ?? [];
                                            $wins = $record['W'] ?? null;
                                            $losses = $record['L'] ?? null;
                                            $pctValue = array_key_exists('Pct', (array)$record) ? $record['Pct'] : null;
                                            $pct = $pctValue !== null
                                                ? number_format((float)$pctValue, 3, '.', '')
                                                : null;
                                            $postLabel = null;
                                            if ($typeKey === 'Postseason') {
                                                $postLabel = $primarySummary['Postseason']['label'] ?? '-';
                                                if (trim((string)$postLabel) === '') {
                                                    $postLabel = '-';
                                                }
                                            }
                                            $displayValue = $typeKey === 'Postseason'
                                                ? $postLabel
                                                : ($pct ?? '—');
                                            $dataSearch = $typeKey === 'Postseason' ? $postLabel : null;
                                            $dataFilter = $typeKey === 'Postseason' ? $postLabel : null;
                                            ?>
                                            <td class="text-end"><?= $wins ?? '—' ?></td>
                                            <td class="text-end"><?= $losses ?? '—' ?></td>
                                            <td class="text-end"<?= $dataSearch !== null ? ' data-search="' . h($dataSearch) . '"' : '' ?><?= $dataFilter !== null ? ' data-filter="' . h($dataFilter) . '"' : '' ?>>
                                                <?= h($displayValue) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</turbo-frame>
