<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Person $person
 * @var array<int,\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\BlogPost> $blogPosts
 * @var array<int,\App\Model\Entity\TeamSeasonRosters> $rosterEntries
 * @var array<int,\App\Model\Entity\StatBasketGamePerson> $gameStats
 */
$this->assign('title', $person->first_name . ' ' . $person->last_name);
?>
<div class="container py-4">
    <!-- Person Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'index']) ?>">People</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= h($person->first_name) ?> <?= h($person->last_name) ?>
                    </li>
                </ol>
            </nav>
            <h1 class="display-5 mb-3">
                <?= h($person->first_name) ?> <?= h($person->last_name) ?>
                <?php if (!empty($person->nickname)) : ?>
                    <small class="text-muted">"<?= h($person->nickname) ?>"</small>
                <?php endif; ?>
            </h1>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="personTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="seasons-tab" data-bs-toggle="tab" data-bs-target="#seasons"
                    type="button" role="tab" aria-controls="seasons" aria-selected="true">
                <i class="bi bi-trophy me-1"></i>Seasons
            </button>
        </li>
        <?php if (!empty($gameStats)) : ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats"
                        type="button" role="tab" aria-controls="stats" aria-selected="false">
                    <i class="bi bi-graph-up me-1"></i>Stats
                </button>
            </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images"
                    type="button" role="tab" aria-controls="images" aria-selected="false">
                <i class="bi bi-image me-1"></i>Images
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="blog-tab" data-bs-toggle="tab" data-bs-target="#blog"
                    type="button" role="tab" aria-controls="blog" aria-selected="false">
                <i class="bi bi-newspaper me-1"></i>Stories
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="personTabsContent">
        <!-- Seasons Tab -->
        <div class="tab-pane fade show active" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
            <?php if (!empty($rosterEntries)) : ?>
                <div class="list-group">
                    <?php foreach ($rosterEntries as $entry) : ?>
                        <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $entry->team_season->id ?? 0]) ?>"
                           class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h5 class="mb-1">
                                    <?= h($entry->team_season->season->start ?? '') ?>-<?= h($entry->team_season->season->end ?? '') ?>
                                    <?= h($entry->team_season->team->team_name ?? '') ?>
                                </h5>
                                <div>
                                    <?php if (!empty($entry->jersey_number)) : ?>
                                        <span class="badge bg-primary">#<?= h($entry->jersey_number) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($entry->roster_position)) : ?>
                                        <span class="badge bg-secondary"><?= h($entry->roster_position) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($entry->class_year)) : ?>
                                        <span class="badge bg-info"><?= h($entry->class_year) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="alert alert-info">No season information available.</div>
            <?php endif; ?>
        </div>

        <!-- Stats Tab -->
        <?php if (!empty($gameStats)) : ?>
            <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                <h4 class="mb-3">Recent Game Stats</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Opponent</th>
                                <th>PTS</th>
                                <th>REB</th>
                                <th>AST</th>
                                <th>FG</th>
                                <th>3PT</th>
                                <th>FT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gameStats as $stat) : ?>
                                <tr>
                                    <td><?= h($stat->game->game_date?->format('M j, Y')) ?></td>
                                    <td>
                                        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $stat->game->id ?? 0]) ?>">
                                            vs <?= h($stat->game->opponent->opponent_name ?? 'Unknown') ?>
                                        </a>
                                    </td>
                                    <td><?= h($stat->pts ?? 0) ?></td>
                                    <td><?= h($stat->reb ?? 0) ?></td>
                                    <td><?= h($stat->ast ?? 0) ?></td>
                                    <td><?= h($stat->fgm ?? 0) ?>-<?= h($stat->fga ?? 0) ?></td>
                                    <td><?= h($stat->fg3m ?? 0) ?>-<?= h($stat->fg3a ?? 0) ?></td>
                                    <td><?= h($stat->ftm ?? 0) ?>-<?= h($stat->fta ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Images Tab -->
        <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">
            <?php if (!empty($images)) : ?>
                <div class="row g-3">
                    <?php foreach ($images as $image) : ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <img src="/images/serve/<?= h($image->id) ?>?w=300&h=300&fit=cover"
                                     class="card-img-top"
                                     alt="<?= h($image->filename) ?>"
                                     loading="lazy">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="alert alert-info">No images available for this person.</div>
            <?php endif; ?>
        </div>

        <!-- Blog/Stories Tab -->
        <div class="tab-pane fade" id="blog" role="tabpanel" aria-labelledby="blog-tab">
            <?php if (!empty($blogPosts)) : ?>
                <div class="row g-4">
                    <?php foreach ($blogPosts as $post) : ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="row g-0">
                                    <?php if (!empty($post->hero_image_id)) : ?>
                                        <div class="col-md-4">
                                            <img src="/images/serve/<?= h($post->hero_image_id) ?>?w=400&h=300&fit=cover"
                                                 class="img-fluid rounded-start h-100 w-100"
                                                 style="object-fit: cover;"
                                                 alt="<?= h($post->title) ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                    <div class="<?= !empty($post->hero_image_id) ? 'col-md-8' : 'col-12' ?>">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $post->slug]) ?>"
                                                   class="text-decoration-none">
                                                    <?= h($post->title) ?>
                                                </a>
                                            </h5>
                                            <?php if (!empty($post->excerpt)) : ?>
                                                <p class="card-text"><?= h($post->excerpt) ?></p>
                                            <?php endif; ?>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?= h($post->published_at?->format('F j, Y')) ?>
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="alert alert-info">No stories available for this person.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
