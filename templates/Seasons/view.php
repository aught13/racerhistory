<?php
/**
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var array<int,\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\Game> $games
 * @var array<int,\App\Model\Entity\TeamSeasonRosters> $roster
 * @var array<string,int|float|null> $recordSummary
 * @var array{playerStats?:\Cake\Collection\CollectionInterface|null,teamStats?:object|null,opponentStats?:object|null}|null $seasonStats
 * @var string|null $seasonStatsElement
 * @var array<string,string> $seasonStatsColumns
 * @var array<int,\App\Model\Entity\BlogPost> $previewPosts
 * @var array<int,\App\Model\Entity\BlogPost> $reviewPosts
 * @var array<int,\App\Model\Entity\BlogPost> $otherPosts
 */

$seasonStart = $teamSeason->season->start ?? '';
$seasonEnd = $teamSeason->season->end ?? '';
$seasonLabel = ($seasonStart !== '' && $seasonEnd !== '')
    ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
    : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
$teamName = $teamSeason->team->team_name ?? 'Team';
$sportName = $teamSeason->team->sport->sport_name ?? 'Sport';
$genderDisplay = match ($teamSeason->team->gender ?? '') {
    'M' => "Men's",
    'F' => "Women's",
    'C' => 'Co-ed',
    default => (string)($teamSeason->team->gender ?? ''),
};
$overallWins = $recordSummary['overall_wins'] ?? null;
$overallLosses = $recordSummary['overall_losses'] ?? null;
$overallPct = $recordSummary['overall_pct'] ?? null;
$confWins = $recordSummary['conf_wins'] ?? null;
$confLosses = $recordSummary['conf_losses'] ?? null;
$confPct = $recordSummary['conf_pct'] ?? null;
$overallRecord = ($overallWins !== null || $overallLosses !== null)
    ? sprintf('%s-%s', $overallWins ?? '—', $overallLosses ?? '—')
    : null;
$confRecord = ($confWins !== null || $confLosses !== null)
    ? sprintf('%s-%s', $confWins ?? '—', $confLosses ?? '—')
    : null;
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
<?= $this->Html->script('season-view-init-loader', ['type' => 'module']) ?>
<?php $this->end(); ?>

<div class="container py-4 season-view" data-season-view>
    <style>
        @media (prefers-color-scheme: dark) {
            [data-season-view] #season-games-table a {
                color: #001f3f;
            }
        }
    </style>
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
                <div>
                    <h1 class="display-6 mb-2">
                        <?= h($teamName) ?>
                        <small class="text-muted"><?= h($seasonLabel) ?></small>
                    </h1>
                    <p class="lead mb-2 text-muted"><?= h($sportName) ?> · <?= h($genderDisplay) ?></p>
                    <?php if (!empty($teamSeason->league)) : ?>
                        <p class="season-hero-meta mb-0">
                            <?= h($teamSeason->league) ?>
                            <?php if (!empty($teamSeason->league_finish)) : ?>
                                · <?= h($teamSeason->league_finish) ?> finish
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="season-hero-stats d-flex flex-wrap gap-3">
                    <div class="season-stat-card">
                        <span class="season-stat-label">Overall</span>
                        <strong class="season-stat-value"><?= h($overallRecord ?? '—') ?></strong>
                        <span class="season-stat-subtext"><?= $overallPct !== null ? number_format((float)$overallPct, 3, '.', '') : '—' ?></span>
                    </div>
                    <div class="season-stat-card">
                        <span class="season-stat-label">Conference</span>
                        <strong class="season-stat-value"><?= h($confRecord ?? '—') ?></strong>
                        <span class="season-stat-subtext"><?= $confPct !== null ? number_format((float)$confPct, 3, '.', '') : '—' ?></span>
                    </div>
                    <div class="season-stat-card">
                        <span class="season-stat-label">Postseason</span>
                        <strong class="season-stat-value"><?= h($teamSeason->last_post_game ?: '—') ?></strong>
                    </div>
                    <?php if (!empty($teamSeason->team_season_notes)) : ?>
                        <div class="season-notes mt-4">
                            <h3 class="h6 text-uppercase text-muted">Season Notes</h3>
                            <p class="mb-0"><?= h($teamSeason->team_season_notes) ?></p>
                        </div>
                    <?php endif; ?>
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
                                                    $badgeStyle = 'background-color:#6c757d;color:#ffffff;';
                                                    if (!empty($type->post)) {
                                                        if (!empty($type->conf)) {
                                                            $badgeStyle = 'background-color:#FFD700;color:#001f3f;';
                                                        } else {
                                                            $badgeStyle = 'background-color:#001f3f;color:#FFD700;';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="badge" style="<?= $badgeStyle ?>">
                                                        <?= h($typeLabel) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($game->place_name ?? '') ?><?php if (!empty($game->place_state)) : ?>, <?= h($game->place_state) ?><?php endif; ?>
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
                        <div class="table-responsive">
                            <table id="season-roster-table" class="table table-striped table-hover align-middle js-datatable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Player</th>
                                        <th>Position</th>
                                        <th>Class</th>
                                        <th>Profile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roster as $entry) : ?>
                                        <tr>
                                            <td><?= h($entry->roster_number ?? '') ?></td>
                                            <td><?= h($entry->person->first_name ?? '') ?> <?= h($entry->person->last_name ?? '') ?></td>
                                            <td><?= h($entry->roster_position ?? '') ?></td>
                                            <td><?= h($entry->class_year ?? '') ?></td>
                                            <td>
                                                <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $entry->person->id ?? 0]) ?>" class="btn btn-sm btn-outline-primary">
                                                    View Profile
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
                        <div class="row g-3">
                            <?php foreach ($images as $image) : ?>
                                <div class="col-6 col-md-4">
                                    <div class="season-photo">
                                        <img src="/images/serve/<?= h($image->id) ?>?w=480&h=360&fit=cover"
                                             alt="<?= h($image->filename) ?>"
                                             loading="lazy">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
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
