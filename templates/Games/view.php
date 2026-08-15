<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\Game $game
 * @var array<string,mixed> $eav
 * @var array $teamBoxStats
 * @var array $opponentBoxStats
 * @var array $teamPeriodStats
 * @var array $opponentPeriodStats
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var \Cake\Collection\CollectionInterface|null $opponentPlayerStats
 * @var object|null $teamTeamStats
 * @var object|null $opponentTeamStats
 * @var bool $hasPeriodStats
 * @var array<string,string> $fieldLabels
 * @var string|null $statsElement
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\BlogPost> $blogPosts
 * @var \App\View\AppView $this
 * @var array $match
 */

$teamName = $game->team_season->team->team_name ?? 'Team';
$teamNickname = $game->team_season->team->team_nickname ?? $teamName;
$opponentName = $game->opponent->opponent_name ?? 'Opponent';
$opponentAbbr = $game->opponent->opponent_abbr ?? $opponentName;
$gameDate = $game->game_date?->format('M j, Y') ?? '';
$fullDate = $game->game_date?->format('l, F j, Y') ?? '';
$seasonStart = $game->team_season->season->start ?? '';
$seasonEnd = $game->team_season->season->end ?? '';
$seasonLabel = $seasonStart && $seasonEnd
    ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
    : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
$resultFlag = $game->result_flag ?? null;
$sportName = $game->team_season->team->sport_name ?? $game->team_season->team->sport->sport_name ?? 'Sport';

$periods = 2;
$otPeriods = 0;
foreach ($eav as $key => $value) {
    if (preg_match('/^period_(\d+)_team$/', (string)$key, $match)) {
        $periods = max($periods, (int)$match[1]);
    }
    if (preg_match('/^overtime_(\d+)_team$/', (string)$key, $match)) {
        $otPeriods = max($otPeriods, (int)$match[1]);
    }
}

$periodLabels = [];
for ($i = 1; $i <= $periods; $i++) {
    $periodLabels[] = (string)$i;
}
for ($i = 1; $i <= $otPeriods; $i++) {
    $periodLabels[] = $otPeriods > 1 ? 'OT' . $i : 'OT';
}

$officials = array_filter([
    $eav['official_1'] ?? null,
    $eav['official_2'] ?? null,
    $eav['official_3'] ?? null,
]);

$gameTimeDisplay = '';
if (!empty($game->game_time)) {
    if ($game->game_time instanceof DateTimeInterface) {
        $gameTimeDisplay = $game->game_time->format('g:i A');
    } else {
        try {
            $timeObj = new DateTime($game->game_time);
            $gameTimeDisplay = $timeObj->format('g:i A');
        } catch (Throwable $e) {
            $gameTimeDisplay = (string)$game->game_time;
        }
    }
}

$this->assign('title', sprintf('%s vs %s', $teamNickname, $opponentName));

$this->start('css'); ?>
<?php $this->end(); ?>

<div class="container py-4 game-view" data-game-view data-controller="game-view">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a>
            </li>
            <?php if (!empty($game->team_season->id)) : ?>
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $game->team_season->id]) ?>">
                        <?= h($seasonLabel ?: $sportName) ?> Season
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?= h($gameDate) ?>
            </li>
        </ol>
    </nav>

    <div class="game-hero card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-lg-5 text-center text-lg-start">
                    <div class="game-scoreline">
                        <h1 class="display-6 mb-0"><?= h($teamNickname) ?></h1>
                        <p class="text-muted mb-2"><?= h($teamName) ?></p>
                    </div>
                </div>
                <div class="col-lg-2 text-center">
                    <div class="game-score-badge">
                        <span class="game-score-value"><?= h($game->pts_mur ?? '-') ?></span>
                        <span class="game-score-divider">-</span>
                        <span class="game-score-value"><?= h($game->pts_opp ?? '-') ?></span>
                        <?php if (!empty($resultFlag)) : ?>
                            <span class="badge bg-<?= $resultFlag === 'W' ? 'success' : ($resultFlag === 'L' ? 'danger' : 'secondary') ?> game-result-badge">
                                <?= h($resultFlag) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 text-center text-lg-end">
                    <div class="game-scoreline">
                        <h1 class="display-6 mb-0"><?= h($opponentName) ?></h1>
                        <p class="text-muted mb-2"><?= h($opponentAbbr) ?></p>
                    </div>
                </div>
            </div>

            <div class="row mt-4 g-3">
                <div class="col-lg-7">
                    <div class="game-meta-grid">
                        <div class="game-meta-item">
                            <span class="game-meta-label">Date</span>
                            <span class="game-meta-value"><?= h($fullDate) ?></span>
                        </div>
                        <div class="game-meta-item">
                            <span class="game-meta-label">Location</span>
                            <span class="game-meta-value"><?= h(trim((string)($game->place_city ?? ''))) ?><?= !empty($game->place_state) ? ', ' . h($game->place_state) : '' ?></span>
                            <?php if (!empty($game->site_name)) : ?>
                                <span class="game-meta-subtext"><?= h($game->site_name) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($game->game_type->type_name)) : ?>
                            <div class="game-meta-item">
                                <span class="game-meta-label">Type</span>
                                <span class="game-meta-value">
                                    <span class="badge bg-info game-type-badge"><?= h($game->game_type->type_name) ?></span>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($gameTimeDisplay)) : ?>
                            <div class="game-meta-item">
                                <span class="game-meta-label">Tipoff</span>
                                <span class="game-meta-value"><?= h($gameTimeDisplay) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($game->attendance)) : ?>
                            <div class="game-meta-item">
                                <span class="game-meta-label">Attendance</span>
                                <span class="game-meta-value"><?= h($game->attendance) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($game->game_duration)) : ?>
                            <div class="game-meta-item">
                                <span class="game-meta-label">Duration</span>
                                <span class="game-meta-value"><?= h($game->game_duration) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="game-line-score">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col"></th>
                                        <?php foreach ($periodLabels as $label) : ?>
                                            <th scope="col" class="text-center"><?= h($label) ?></th>
                                        <?php endforeach; ?>
                                        <th scope="col" class="text-center">F</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope="row" class="text-nowrap"><?= h($teamNickname) ?></th>
                                        <?php for ($i = 1; $i <= $periods; $i++) : ?>
                                            <td class="text-center"><?= h($eav['period_' . $i . '_team'] ?? '') ?></td>
                                        <?php endfor; ?>
                                        <?php for ($i = 1; $i <= $otPeriods; $i++) : ?>
                                            <td class="text-center"><?= h($eav['overtime_' . $i . '_team'] ?? '') ?></td>
                                        <?php endfor; ?>
                                        <td class="text-center fw-bold"><?= h($game->pts_mur ?? '') ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="text-nowrap"><?= h($opponentAbbr) ?></th>
                                        <?php for ($i = 1; $i <= $periods; $i++) : ?>
                                            <td class="text-center"><?= h($eav['period_' . $i . '_opponent'] ?? '') ?></td>
                                        <?php endfor; ?>
                                        <?php for ($i = 1; $i <= $otPeriods; $i++) : ?>
                                            <td class="text-center"><?= h($eav['overtime_' . $i . '_opponent'] ?? '') ?></td>
                                        <?php endfor; ?>
                                        <td class="text-center fw-bold"><?= h($game->pts_opp ?? '') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($officials)) : ?>
                            <div class="game-officials mt-2">
                                <span class="game-meta-label">Officials</span>
                                <span class="game-meta-value"><?= h(implode(', ', $officials)) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($game->notes)) : ?>
                            <p class="text-muted small mt-2 mb-0"><?= h($game->notes) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <section class="card shadow-sm game-section">
                <div class="card-header">
                    <h2 class="h5 mb-0">Box Score</h2>
                </div>
                <div class="card-body">
                    <turbo-frame id="game-stats-frame" src="<?= $this->Url->build(['action' => 'stats', $game->id]) ?>" data-turbo-cache="false">
                        <p class="text-muted mb-0">Loading game stats...</p>
                    </turbo-frame>
                </div>
            </section>

            <?php if (!empty($images)) : ?>
                <section class="card shadow-sm game-section" id="game-images">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Game Photos</h2>
                    </div>
                    <div class="card-body">
                        <div class="game-photos-grid" data-game-image-gallery>
                            <?php foreach ($images as $image) : ?>
                                <div class="game-photo-thumb">
                                    <?= $this->ImageServe->picture(
                                        $image,
                                        [],
                                        [
                                            'alt' => (string)$image->filename,
                                            'data-image-url' => $this->ImageServe->urlForImage($image),
                                            'data-image-filename' => (string)$image->filename,
                                            'class' => 'game-photo-thumb-img',
                                            'style' => 'width: 240px; height: 180px; object-fit: cover;',
                                        ],
                                    ) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <div class="game-image-modal" data-game-image-modal style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 1050; align-items: center; justify-content: center;">
                    <button type="button" class="game-image-modal-close" aria-label="Close" data-modal-close style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer; z-index: 1051;">x</button>
                    <picture class="game-image-modal-container" style="max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
                        <source type="image/webp" data-modal-image-webp>
                        <img src="" alt="" data-modal-image data-modal-image-fallback style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                    </picture>
                </div>
            <?php endif; ?>

            <?php if (!empty($blogPosts)) : ?>
                <section class="card shadow-sm game-section" id="game-stories">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Game Stories</h2>
                    </div>
                    <div class="card-body" data-game-blog>
                        <?= $this->element('blog/list_items', ['paginatedPosts' => $blogPosts]) ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
