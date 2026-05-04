<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\Person $person
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\BlogPost> $blogPosts
 * @var array<int,\App\Model\Entity\TeamSeasonRosters> $rosterEntries
 * @var array<int,array{sport:\App\Model\Entity\Sport,rosters:array<int,array{roster:\App\Model\Entity\TeamSeasonRosters,teamSeason:\App\Model\Entity\TeamSeason}>}> $rostersBySport
 * @var array<int,array{sport:\App\Model\Entity\Sport,totals:array,seasons:array<int,array{teamSeason:\App\Model\Entity\TeamSeason,stats:array}>,minYear:int|null,maxYear:int|null}> $careerStatsBySport
 * @var array<int,array{sportId:int,sport:\App\Model\Entity\Sport,seasons:array<int,array{teamSeason:\App\Model\Entity\TeamSeason,rosterId:int,label:string,startYear:int}>,activeSeasonId:int|null}> $gameLogGroups
 * @var \App\View\AppView $this
 */

$first = (string)($person->first ?? $person->first_name ?? '');
$last = (string)($person->last ?? $person->last_name ?? '');
$full = (string)($person->full ?? '');
$display = (string)($person->display ?? '');
$name = $full !== '' ? $full : trim($first . ' ' . $last);
if ($name === '') {
    $name = $display !== '' ? $display : 'Unknown';
}
$nickname = (string)($person->nickname ?? '');
$birth = $person->birth ?? null;
$death = $person->death ?? null;
$this->assign('title', $name);

$heroImageId = null;
if (!empty($person->person_image) && is_numeric($person->person_image)) {
    $heroImageId = (int)$person->person_image;
} elseif (!empty($images) && !empty($images[0]->id)) {
    $heroImageId = (int)$images[0]->id;
}
$heroImageUrl = $heroImageId
    ? $this->ImageServe->url($heroImageId, ['variant' => 'medium'])
    : null;

$teams = [];
foreach ($rosterEntries as $entry) {
    $teamName = $entry->team_season->team->team_name ?? null;
    if ($teamName) {
        $teams[$teamName] = true;
    }
}
$teamList = array_keys($teams);
sort($teamList, SORT_NATURAL | SORT_FLAG_CASE);

$formatSeasonLabel = static function ($season): string {
    $seasonStart = (string)($season->start ?? '');
    $seasonEnd = (string)($season->end ?? '');
    if ($seasonStart === '' && $seasonEnd === '') {
        return '';
    }

    $suffix = $seasonEnd !== '' ? substr($seasonEnd, -2) : '';

    return trim($seasonStart . '-' . $suffix, '-');
};

$this->start('script');
echo $this->Html->script('person-game-log-tabs-loader', ['type' => 'module', 'ext' => '.mjs']);
echo $this->Html->script('person-blog-popover-loader', ['type' => 'module', 'ext' => '.mjs']);
$this->end();

?>
<div class="container py-4 person-view" data-person-view>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'index']) ?>">People</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= h($name) ?>
            </li>
        </ol>
    </nav>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-3 text-center">
                    <?php if ($heroImageUrl) : ?>
                        <img
                            src="<?= h($heroImageUrl) ?>"
                            alt="<?= h($name) ?>"
                            class="img-fluid rounded shadow-sm"
                            loading="lazy">
                    <?php else : ?>
                        <div
                            class="d-inline-flex align-items-center justify-content-center rounded shadow-sm bg-light"
                            style="width: 200px; height: 200px;">
                            <i class="fa-regular fa-user fa-3x text-muted" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-lg-9">
                    <h1 class="display-6 mb-2">
                        <?= h($name) ?>
                        <?php if ($nickname !== '') : ?>
                            <small class="text-muted">"<?= h($nickname) ?>"</small>
                        <?php endif; ?>
                    </h1>
                    <?php if ($display !== '' && $display !== $name) : ?>
                        <p class="text-muted mb-2"><?= h($display) ?></p>
                    <?php endif; ?>
                    <?php if ($birth || $death) : ?>
                        <p class="mb-2">
                            <?php if ($birth) : ?>
                                <span class="me-3"><strong>Born:</strong> <?= h((string)$birth) ?></span>
                            <?php endif; ?>
                            <?php if ($death) : ?>
                                <span><strong>Died:</strong> <?= h((string)$death) ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($teamList)) : ?>
                        <div class="mt-3">
                            <h2 class="h6 text-uppercase text-muted mb-2">Teams</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($teamList as $teamName) : ?>
                                    <span class="badge bg-light text-dark border"><?= h($teamName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php if (!empty($rostersBySport)) : ?>
            <?php foreach ($rostersBySport as $sportData) : ?>
                <?php
                $sportName = $sportData['sport']->sport_name ?? 'Sport';
                ?>
                <div class="col-12">
                    <section class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h2 class="h5 mb-0">Seasons · <?= h($sportName) ?></h2>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($sportData['rosters'] as $rosterData) : ?>
                                    <?php
                                    $entry = $rosterData['roster'] ?? null;
                                    $teamSeason = $rosterData['teamSeason'] ?? null;
                                    $season = $teamSeason?->season ?? null;
                                    $team = $teamSeason?->team ?? null;
                                    $seasonLabel = $season ? $formatSeasonLabel($season) : '';
                                    $teamName = $team->team_name ?? 'Team';
                                    $seasonUrl = $this->Url->build([
                                        'controller' => 'Seasons',
                                        'action' => 'view',
                                        $teamSeason->id ?? 0,
                                    ]);
                                    $number = $entry?->jersey_number ?? $entry?->roster_number ?? null;
                                    $position = $entry?->roster_position ?? null;
                                    $classYear = $entry?->class_year ?? $entry?->roster_year ?? null;
                                    $height = $entry?->roster_height ?? null;
                                    $weight = $entry?->roster_weight ?? null;
                                    $rowClasses = implode(
                                        ' ',
                                        [
                                            'd-flex',
                                            'w-100',
                                            'justify-content-between',
                                            'align-items-center',
                                            'flex-wrap',
                                            'gap-2',
                                        ],
                                    );
                                    ?>
                                    <a href="<?= $seasonUrl ?>" class="list-group-item list-group-item-action">
                                        <div
                                            class="<?= h($rowClasses) ?>">
                                            <div>
                                                <h5 class="mb-1">
                                                    <?= h($teamName) ?>
                                                    <?php if ($seasonLabel !== '') : ?>
                                                        <small class="text-muted ms-2"><?= h($seasonLabel) ?></small>
                                                    <?php endif; ?>
                                                </h5>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if (!empty($number)) : ?>
                                                    <span class="badge bg-primary">
                                                        #<?= h((string)$number) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($position)) : ?>
                                                    <span class="badge bg-secondary">
                                                        <?= h((string)$position) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($classYear)) : ?>
                                                    <span class="badge bg-info text-dark">
                                                        <?= h((string)$classYear) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($height)) : ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= h((string)$height) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($weight)) : ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= h((string)$weight) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="alert alert-info">No season information available.</div>
            </div>
        <?php endif; ?>
        <?php if (!empty($careerStatsBySport)) : ?>
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Career Statistics</h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($careerStatsBySport as $careerData) : ?>
                            <?php
                            $sportName = strtolower((string)($careerData['sport']->sport_name ?? ''));
                            $careerElement = 'Admin/' . $sportName . '_person_career_stats';
                            $totals = $careerData['totals'];
                            $seasons = $careerData['seasons'];
                            ?>
                            <h3 class="h6 text-uppercase text-muted mb-3">
                                <?= h($careerData['sport']->sport_name ?? 'Sport') ?> Career Totals
                            </h3>
                            <?php if (!empty($seasons)) : ?>
                                <?= $this->element($careerElement, [
                                    'seasons' => $seasons,
                                    'totals' => $totals,
                                    'minYear' => $careerData['minYear'] ?? null,
                                    'maxYear' => $careerData['maxYear'] ?? null,
                                ]) ?>
                            <?php else : ?>
                                <?= $this->element('Admin/person_career_empty') ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <section class="card shadow-sm" data-person-game-log>
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2 class="h5 mb-0">Game Log</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($gameLogGroups)) : ?>
                        <?php foreach ($gameLogGroups as $group) : ?>
                            <?php
                            $sportName = $group['sport']->sport_name ?? 'Sport';
                            $sportId = $group['sportId'];
                            $activeSeasonId = $group['activeSeasonId'];
                            ?>
                            <div class="mb-4">
                                <h3 class="h6 text-uppercase text-muted mb-3">
                                    <?= h($sportName) ?> Game Log
                                </h3>
                                <ul class="nav nav-tabs" role="tablist">
                                    <?php foreach ($group['seasons'] as $seasonData) : ?>
                                        <?php
                                        $teamSeason = $seasonData['teamSeason'];
                                        $seasonId = (int)($teamSeason->id ?? 0);
                                        $seasonLabel = $seasonData['label'];
                                        $tabId = 'person-game-log-tab-' . $sportId . '-' . $seasonId;
                                        $paneId = 'person-game-log-pane-' . $sportId . '-' . $seasonId;
                                        $tabLabelId = 'person-game-log-tab-' . $sportId . '-' . $seasonId;
                                        $frameId = 'person-game-log-frame-' . (int)$person->id . '-' . $seasonId;
                                        $isActive = $activeSeasonId === $seasonId;
                                        $frameSrc = $this->Url->build([
                                            'controller' => 'People',
                                            'action' => 'gameLog',
                                            $person->id,
                                            $seasonId,
                                        ]);
                                        ?>
                                        <li class="nav-item" role="presentation">
                                            <button
                                                class="nav-link<?= $isActive ? ' active' : '' ?>"
                                                id="<?= h($tabId) ?>"
                                                type="button"
                                                role="tab"
                                                data-bs-toggle="tab"
                                                data-bs-target="#<?= h($paneId) ?>"
                                                data-person-game-log-tab
                                                data-person-game-log-frame="<?= h($frameId) ?>"
                                                aria-controls="<?= h($paneId) ?>"
                                                aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                                                <?= h($seasonLabel) ?>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="tab-content border border-top-0 rounded-bottom p-3">
                                    <?php foreach ($group['seasons'] as $seasonData) : ?>
                                        <?php
                                        $teamSeason = $seasonData['teamSeason'];
                                        $seasonId = (int)($teamSeason->id ?? 0);
                                        $paneId = 'person-game-log-pane-' . $sportId . '-' . $seasonId;
                                        $frameId = 'person-game-log-frame-' . (int)$person->id . '-' . $seasonId;
                                        $tabLabelId = 'person-game-log-tab-' . $sportId . '-' . $seasonId;
                                        $isActive = $activeSeasonId === $seasonId;
                                        $frameSrc = $this->Url->build([
                                            'controller' => 'People',
                                            'action' => 'gameLog',
                                            $person->id,
                                            $seasonId,
                                        ]);
                                        ?>
                                        <div
                                            class="tab-pane fade<?= $isActive ? ' show active' : '' ?>"
                                            id="<?= h($paneId) ?>"
                                            role="tabpanel"
                                            aria-labelledby="<?= h($tabLabelId) ?>">
                                            <turbo-frame
                                                id="<?= h($frameId) ?>"
                                                data-person-game-log-frame
                                                data-person-game-log-src="<?= h($frameSrc) ?>"
                                                <?= $isActive ? 'src="' . h($frameSrc) . '"' : '' ?>>
                                                <p class="text-muted mb-0">Loading game log...</p>
                                            </turbo-frame>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-muted mb-0">No game stats available yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            <section class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2 class="h5 mb-0">Images</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)) : ?>
                        <div class="row g-3">
                            <?php foreach ($images as $image) : ?>
                                <div class="col-6">
                                    <div class="card h-100">
                                        <img
                                            src="<?= h($this->ImageServe->urlForImage($image, ['w' => 300, 'h' => 300, 'fit' => 'cover'])) ?>"
                                            class="card-img-top"
                                            alt="<?= h($image->filename) ?>"
                                            loading="lazy">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info mb-0">No images available for this person.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            <section class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2 class="h5 mb-0">Stories</h2>
                </div>
                <div class="card-body" data-person-blog-popovers>
                    <?php if (!empty($blogPosts)) : ?>
                        <div class="list-group">
                            <?php foreach ($blogPosts as $post) : ?>
                                <?php
                                $viewUrl = $this->Url->build([
                                    'controller' => 'Blog',
                                    'action' => 'view',
                                    $post->slug,
                                ]);
                                $popoverUrl = $this->Url->build([
                                    'controller' => 'Blog',
                                    'action' => 'popover',
                                    $post->slug,
                                ]);
                                $heroImageSrc = '';
                                if (!empty($post->hero_image_id)) {
                                    $heroImageSrc = $this->ImageServe->url($post->hero_image_id, ['w' => 200, 'h' => 150, 'fit' => 'cover']);
                                }
                                ?>
                                <a
                                    href="<?= h($viewUrl) ?>"
                                    class="list-group-item list-group-item-action"
                                    data-person-blog-popover
                                    data-person-blog-popover-url="<?= h($popoverUrl) ?>"
                                    data-person-blog-popover-title="<?= h($post->title) ?>">
                                    <div class="d-flex gap-3">
                                        <?php if (!empty($post->hero_image_id)) : ?>
                                            <div
                                                style="flex-shrink: 0; width: 120px; height: 90px;">
                                                <img
                                                    src="<?= h($heroImageSrc) ?>"
                                                    class="img-fluid rounded"
                                                    alt="<?= h($post->title) ?>"
                                                    style="object-fit: cover; width: 100%; height: 100%;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h3 class="h6 mb-1"><?= h($post->title) ?></h3>
                                            <p class="text-muted small mb-2">
                                                <?php if ($post->published_at instanceof DateTimeInterface) : ?>
                                                    <?= h($post->published_at->format('M j, Y')) ?>
                                                <?php else : ?>
                                                    <?= h($post->published_at ?? '') ?>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($post->excerpt)) : ?>
                                                <p class="small mb-0"><?= h($post->excerpt) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info mb-0">No stories available for this person.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
