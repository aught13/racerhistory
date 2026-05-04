<?php
/**
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Image> $images
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Game> $games
 * @var array<int,\App\Model\Entity\TeamSeasonRosters> $roster
 * @var array<string,mixed> $recordSummary
 * @var array{playerStats?:\Cake\Collection\CollectionInterface|null,teamStats?:object|null,opponentStats?:object|null}|null $seasonStats
 * @var string|null $seasonStatsElement
 * @var array<string,string> $seasonStatsColumns
 * @var array<int,\App\Model\Entity\BlogPost> $previewPosts
 * @var array<int,\App\Model\Entity\BlogPost> $reviewPosts
 * @var array<int,\App\Model\Entity\BlogPost> $otherPosts
 * @var \App\View\AppView $this
 */

$seasonStart = $teamSeason->season->start ?? '';
$seasonEnd = $teamSeason->season->end ?? '';
$seasonLabel = $seasonStart !== '' && $seasonEnd !== ''
    ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
    : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
$teamName = $teamSeason->team->team_name ?? 'Team';
$hasRecord = static function (array $record): bool {
    $wins = (int)($record['W'] ?? 0);
    $losses = (int)($record['L'] ?? 0);
    $ties = (int)($record['T'] ?? 0);

    return ($wins + $losses + $ties) > 0;
};

$formatRecord = static function (array $record): ?string {
    $wins = $record['W'] ?? null;
    $losses = $record['L'] ?? null;
    $ties = $record['T'] ?? null;

    if ($wins === null && $losses === null && $ties === null) {
        return null;
    }

    $wins = $wins ?? 0;
    $losses = $losses ?? 0;
    $ties = $ties ?? 0;

    if ($ties > 0) {
        return sprintf('%s-%s-%s', $wins, $losses, $ties);
    }

    return sprintf('%s-%s', $wins, $losses);
};

$recordGroups = [];
foreach ($recordSummary as $groupKey => $groupData) {
    if (!is_array($groupData)) {
        continue;
    }
    $label = $groupData['label'] ?? $groupKey;
    $totals = is_array($groupData['totals'] ?? null) ? $groupData['totals'] : [];
    $splits = is_array($groupData['splits'] ?? null) ? $groupData['splits'] : [];

    if ($groupKey !== 'Overall' && !$hasRecord($totals)) {
        continue;
    }

    $recordGroups[] = [
        'label' => $label,
        'totals' => $totals,
        'splits' => $splits,
    ];
}
$heroImageId = $teamSeason->team_season_image ?: null;

$this->assign('title', $teamName . ' ' . $seasonLabel . ' Season');

$this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<?php $this->end(); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<?php $this->start('script'); ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<?= $this->Html->script('season-view-init-loader', ['type' => 'module', 'ext' => '.mjs']) ?>
<?php $this->end(); ?>

<div class="container py-4 season-view" data-season-view>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'index']) ?>">Seasons</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= h($teamName) ?> <?= h($seasonLabel) ?>
            </li>
        </ol>
    </nav>

    <?php if (!empty($heroImageId)) : ?>
        <div class="season-hero-media mb-4">
            <img
                src="/images/serve/<?= h($heroImageId) ?>?w=1400&h=720&fit=cover"
                alt="<?= h($teamName) ?> <?= h($seasonLabel) ?> Season"
                class="img-fluid rounded season-hero-image"
                loading="lazy">
        </div>
    <?php endif; ?>

    <div class="season-hero card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                <div class="season-hero-stats d-flex flex-wrap gap-3">
                    <h1 class="display-6 mb-2">
                        <?= h($teamName) ?>
                        <small class="text-muted"><?= h($seasonLabel) ?></small>
                    </h1>
                    <?php if (!empty($teamSeason->league)) : ?>
                        <p class="season-hero-meta mb-0">
                            <?= h($teamSeason->league) ?>
                            <?php if (!empty($teamSeason->league_finish)) : ?>
                                · <?= h($teamSeason->league_finish) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($teamSeason->team_season_notes)) : ?>
                        <p class="mb-0"><?= h($teamSeason->team_season_notes) ?></p>
                    <?php endif; ?>
                </div>
                <div class="season-hero-stats d-flex flex-wrap gap-3">
                    <?php foreach ($recordGroups as $group) : ?>
                        <?php
                        $totals = $group['totals'];
                        $record = $formatRecord($totals);
                        $pct = $totals['Pct'] ?? null;
                        $splits = $group['splits'] ?? [];
                        $splitLines = [];
                        foreach ($splits as $splitLabel => $splitRecord) {
                            if ($splitLabel === 'By Type') {
                                continue;
                            }
                            if (!is_array($splitRecord) || !$hasRecord($splitRecord)) {
                                continue;
                            }
                            $splitLines[] = [
                                'label' => $splitLabel,
                                'value' => $formatRecord($splitRecord),
                            ];
                        }
                        $byTypeLines = [];
                        if (!empty($splits['By Type']) && is_array($splits['By Type'])) {
                            foreach ($splits['By Type'] as $typeLabel => $typeRecord) {
                                if (!is_array($typeRecord) || !$hasRecord($typeRecord)) {
                                    continue;
                                }
                                $byTypeLines[] = [
                                    'label' => $typeLabel,
                                    'value' => $formatRecord($typeRecord),
                                ];
                            }
                        }
                        ?>
                        <div class="season-stat-card">
                            <span class="season-stat-label"><?= h($group['label']) ?></span>
                            <strong class="season-stat-value"><?= h($record ?? '—') ?></strong>
                            <span class="season-stat-subtext"><?= $pct !== null ? number_format((float)$pct, 3, '.', '') : '—' ?></span>
                            <?php if (count($splitLines) + count($byTypeLines) > 1) : ?>
                                <ul class="season-stat-splits list-unstyled mb-0">
                                    <?php foreach ($splitLines as $line) : ?>
                                        <li>
                                            <span class="text-muted"><?= h($line['label']) ?>:</span>
                                            <?= h($line['value'] ?? '—') ?>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php foreach ($byTypeLines as $line) : ?>
                                        <li>
                                            <span class="text-muted"><?= h($line['label']) ?>:</span>
                                            <?= h($line['value'] ?? '—') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <?php if (!empty($previewPosts)) : ?>
                <section class="card shadow-sm season-section">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Season Preview</h2>
                    </div>
                    <div class="card-body" data-season-blog>
                        <?= $this->element('blog/list_items', ['paginatedPosts' => $previewPosts]) ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($reviewPosts)) : ?>
                <section class="card shadow-sm season-section">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Season Recap</h2>
                    </div>
                    <div class="card-body" data-season-blog>
                        <?= $this->element('blog/list_items', ['paginatedPosts' => $reviewPosts]) ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card shadow-sm season-section" id="season-games">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Game Log</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($games)) : ?>
                        <div class="table-responsive">
                                        <table id="season-games-table" class="table table-striped table-hover align-middle js-datatable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Opponent</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Result</th>
                                        <th>Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($games as $game) : ?>
                                        <tr>
                                            <td><?= h($game->game_date?->format('M j, Y')) ?></td>
                                            <td>
                                                <?php
                                                $locationPrefix = '@';
                                                if (!empty($game->hrn) && (int)$game->hrn === 1) {
                                                    $locationPrefix = 'Vs';
                                                } elseif (!empty($game->hrn) && (int)$game->hrn === 3) {
                                                    $locationPrefix = 'vs';
                                                }
                                                ?>
                                                <?= h($locationPrefix) ?> <?= h($game->opponent->opponent_name ?? 'Unknown') ?>
                                            </td>
                                            <td>
                                                <?php $type = $game->game_type ?? null; ?>
                                                <?php $typeLabel = $type?->abr ?: ($type?->label ?? ''); ?>
                                                <?php if (!empty($typeLabel)) : ?>
                                                    <?php
                                                    $badgeClass = 'bg-secondary';
                                                    if (!empty($type->post)) {
                                                        if (!empty($type->conf)) {
                                                            $badgeClass = 'rh-badge-conf-post';
                                                        } else {
                                                            $badgeClass = 'rh-badge-post';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="badge <?= h($badgeClass) ?>">
                                                        <?= h($typeLabel) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($game->place_city ?? '') ?><?php if (!empty($game->place_state)) :
                                                    ?>, <?= h($game->place_state) ?><?php
                                                endif; ?>
                                                <?php if (!empty($game->site_name)) : ?>
                                                    <div class="text-muted small"><?= h($game->site_name) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $resultFlag = $game->result_flag ?? null; ?>
                                                <?php if (!empty($resultFlag)) : ?>
                                                    <span class="badge bg-<?= $resultFlag === 'W' ? 'success' : ($resultFlag === 'L' ? 'danger' : 'secondary') ?>">
                                                        <?= h($resultFlag) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $gameUrl = $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]); ?>
                                                <?php if ($game->pts_mur !== null && $game->pts_opp !== null) : ?>
                                                    <a href="<?= $gameUrl ?>"><?= h($game->pts_mur) ?>-<?= h($game->pts_opp) ?></a>
                                                <?php else : ?>
                                                    <a href="<?= $gameUrl ?>">View</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info mb-0">No games recorded for this season.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card shadow-sm season-section" id="season-stats">
                <div class="card-header">
                    <h2 class="h5 mb-0">Season Statistics</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($seasonStatsElement)) : ?>
                        <?php if (!empty($seasonStats)) : ?>
                            <?= $this->element($seasonStatsElement, [
                                'teamSeason' => $teamSeason,
                                'playerStats' => $seasonStats['playerStats'] ?? null,
                                'teamStats' => $seasonStats['teamStats'] ?? null,
                                'opponentStats' => $seasonStats['opponentStats'] ?? null,
                                'statsColumns' => $seasonStatsColumns ?? [],
                            ]) ?>
                        <?php else : ?>
                            <p class="text-muted mb-0">Season stats are not available yet.</p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="text-muted mb-0">Season stats are not available for this sport.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card shadow-sm season-section" id="season-roster">
                <div class="card-header">
                    <h2 class="h5 mb-0">Roster</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($roster)) : ?>
                        <div class="season-roster-list">
                            <?php foreach ($roster as $entry) : ?>
                                <?php
                                $person = $entry->person ?? null;
                                $displayName = '';
                                if ($person) {
                                    $displayName = (string)($person->display
                                        ?? $person->full
                                        ?? trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? '')));
                                }
                                if ($displayName === '') {
                                    $displayName = 'Unknown';
                                }
                                $personId = $person->id ?? 0;
                                $profileUrl = $personId
                                    ? $this->Url->build(['controller' => 'People', 'action' => 'view', $personId])
                                    : null;
                                $metaChips = [];
                                if (!empty($entry->roster_position)) {
                                    $metaChips[] = $entry->roster_position;
                                }
                                if (!empty($entry->roster_year)) {
                                    $metaChips[] = $entry->roster_year;
                                }
                                if (!empty($entry->roster_height)) {
                                    $metaChips[] = $entry->roster_height;
                                }
                                if (!empty($entry->roster_weight)) {
                                    $metaChips[] = $entry->roster_weight;
                                }
                                ?>
                                <div class="season-roster-card d-flex align-items-center gap-4 w-100 flex-wrap flex-lg-nowrap">
                                    <div class="season-roster-main d-flex align-items-center gap-3 flex-grow-1">
                                        <div class="season-roster-avatar position-relative">
                                            <?= $this->element('person_image', [
                                                'person' => $person,
                                                'size' => 'small',
                                                'class' => 'season-roster-avatar-img',
                                                'style' => 'width: 72px; height: 72px;',
                                            ]) ?>
                                            <?php if ($entry->roster_number !== null && $entry->roster_number !== '') : ?>
                                                <?php
                                                $badgeValue = (string)$entry->roster_number;
                                                $isSingleDigit = strlen($badgeValue) <= 1;
                                                $badgeClass = 'season-roster-badge';
                                                if ($isSingleDigit) {
                                                    $badgeClass .= ' season-roster-badge--single';
                                                }
                                                $badgeRadius = $isSingleDigit ? '50%' : '999px';
                                                $badgeSize = $isSingleDigit ? 'min-width: 24px; width: 24px; height: 24px; padding: 0;' : 'min-width: 24px; height: 24px; padding: 0 5px;';
                                                ?>
                                                <span class="<?= h($badgeClass) ?>" style="position: absolute; right: -4px; bottom: -4px; z-index: 100; background: #002144; color: #ffffff; border: 2px solid var(--rh-surface); border-radius: <?= h($badgeRadius) ?>; <?= h($badgeSize) ?> display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800; line-height: 1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25); box-sizing: border-box;">
                                                    <?= h($badgeValue) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="season-roster-text">
                                            <?php if ($profileUrl) : ?>
                                                <a class="season-roster-name" href="<?= h($profileUrl) ?>" style="color: inherit; text-decoration: none; font-weight: 700;">
                                                    <?= h($displayName) ?>
                                                </a>
                                            <?php else : ?>
                                                <span class="season-roster-name" style="color: inherit; text-decoration: none; font-weight: 700;">
                                                    <?= h($displayName) ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="season-roster-meta">
                                                <?php if (!empty($metaChips)) : ?>
                                                    <?php foreach ($metaChips as $chip) : ?>
                                                        <span class="season-roster-chip"><?= h($chip) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="season-roster-details">
                                        <?php
                                        $birthPlaceLabel = '';
                                        if (!empty($person->birth_place)) {
                                            $birthPlaceLabel = $person->birth_place->place_city;
                                            if (!empty($person->birth_place->place_state)) {
                                                $birthPlaceLabel .= ', ' . $person->birth_place->place_state;
                                            }
                                        }
                                        $previousSchool = $person->person_previous ?? '';
                                        ?>
                                        <span class="season-roster-subtext" data-person-place><i class="fa-solid fa-location-dot"></i> <?= $birthPlaceLabel ? h($birthPlaceLabel) : '—' ?></span>
                                        <span class="season-roster-subtext" data-person-previous><i class="fa-solid fa-school-flag"></i> <?= $previousSchool ? h($previousSchool) : '—' ?></span>
                                    </div>
                                    <?php if ($profileUrl) : ?>
                                        <a class="season-roster-link" href="<?= h($profileUrl) ?>" style="padding: 0.45rem 0.9rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem; text-decoration: none; line-height: 1.2;">
                                            Full Bio <span aria-hidden="true">→</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info mb-0">No roster information available.</div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!empty($images)) : ?>
            <section class="card shadow-sm season-section" id="season-images">
                <div class="card-header">
                    <h2 class="h5 mb-0">Season Photos</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)) : ?>
                        <div class="season-photos-grid" data-season-image-gallery>
                            <?php foreach ($images as $image) : ?>
                                <div class="season-photo-thumb">
                                    <img src="/images/serve/<?= h($image->id) ?>?w=240&h=180&fit=cover"
                                         alt="<?= h($image->filename) ?>"
                                         data-image-id="<?= h($image->id) ?>"
                                         data-image-filename="<?= h($image->filename) ?>"
                                         loading="lazy"
                                         class="season-photo-thumb-img">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Image Popover Modal -->
            <div class="season-image-modal" id="seasonImageModal" data-season-image-modal style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 1050; align-items: center; justify-content: center;">
                <button type="button" class="season-image-modal-close" aria-label="Close" data-modal-close style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer; z-index: 1051;">×</button>
                <picture class="season-image-modal-container" style="max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
                    <source type="image/webp" data-modal-image-webp>
                    <img src="" alt="" data-modal-image data-modal-image-fallback style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                </picture>
            </div>
            <?php endif; ?>

            <?php if (!empty($otherPosts)) : ?>
            <section class="card shadow-sm season-section" id="season-other-posts">
                <div class="card-header">
                    <h2 class="h5 mb-0">More Season Stories</h2>
                </div>
                <div class="card-body" data-season-blog>
                        <?= $this->element('blog/list_items', ['paginatedPosts' => $otherPosts]) ?>
                    </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>
