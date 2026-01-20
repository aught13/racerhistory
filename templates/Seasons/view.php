<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var array<int,\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\BlogPost> $blogPosts
 * @var array<int,\App\Model\Entity\Game> $games
 * @var array<int,\App\Model\Entity\TeamSeasonRosters> $roster
 */
$this->assign('title', ($teamSeason->season->start ?? '') . '-' . ($teamSeason->season->end ?? '') . ' Season');
?>
<div class="container py-4">
    <!-- Season Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'index']) ?>">Seasons</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?>
                    </li>
                </ol>
            </nav>
            <h1 class="display-5 mb-3">
                <?= h($teamSeason->team->team_name ?? 'Team') ?>
                <small class="text-muted"><?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?></small>
            </h1>
            <?php if (!empty($teamSeason->league)) : ?>
                <p class="lead">
                    <?= h($teamSeason->league) ?>
                    <?php if (!empty($teamSeason->league_finish)) : ?>
                        | <?= h($teamSeason->league_finish) ?> finish
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($teamSeason->record_display)) : ?>
                <p class="fs-4">
                    <span class="badge bg-primary"><?= h($teamSeason->record_display) ?> Record</span>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="seasonTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="games-tab" data-bs-toggle="tab" data-bs-target="#games"
                    type="button" role="tab" aria-controls="games" aria-selected="true">
                <i class="bi bi-calendar-event me-1"></i>Games
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="roster-tab" data-bs-toggle="tab" data-bs-target="#roster"
                    type="button" role="tab" aria-controls="roster" aria-selected="false">
                <i class="bi bi-people me-1"></i>Roster
            </button>
        </li>
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
    <div class="tab-content" id="seasonTabsContent">
        <!-- Games Tab -->
        <div class="tab-pane fade show active" id="games" role="tabpanel" aria-labelledby="games-tab">
            <?php if (!empty($games)) : ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Opponent</th>
                                <th>Location</th>
                                <th>Result</th>
                                <th>Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($games as $game) : ?>
                                <tr>
                                    <td><?= h($game->game_date?->format('M j, Y')) ?></td>
                                    <td>
                                        <?php if ($game->hrn === 'H') : ?>
                                            vs
                                        <?php else : ?>
                                            @
                                        <?php endif; ?>
                                        <?= h($game->opponent->opponent_name ?? 'Unknown') ?>
                                    </td>
                                    <td>
                                        <?= h($game->place->city ?? '') ?><?php if (!empty($game->place->state)) : ?>, <?= h($game->place->state) ?><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($game->result)) : ?>
                                            <span class="badge bg-<?= $game->result === 'W' ? 'success' : ($game->result === 'L' ? 'danger' : 'secondary') ?>">
                                                <?= h($game->result) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($game->mur_pts !== null && $game->opp_pts !== null) : ?>
                                            <?= h($game->mur_pts) ?>-<?= h($game->opp_pts) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="alert alert-info">No games recorded for this season.</div>
            <?php endif; ?>
        </div>

        <!-- Roster Tab -->
        <div class="tab-pane fade" id="roster" role="tabpanel" aria-labelledby="roster-tab">
            <?php if (!empty($roster)) : ?>
                <div class="row g-3">
                    <?php foreach ($roster as $rosterEntry) : ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php if (!empty($rosterEntry->jersey_number)) : ?>
                                            #<?= h($rosterEntry->jersey_number) ?>
                                        <?php endif; ?>
                                        <?= h($rosterEntry->person->first_name ?? '') ?> <?= h($rosterEntry->person->last_name ?? '') ?>
                                    </h5>
                                    <p class="card-text">
                                        <?php if (!empty($rosterEntry->roster_position)) : ?>
                                            <span class="badge bg-secondary"><?= h($rosterEntry->roster_position) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($rosterEntry->class_year)) : ?>
                                            <span class="badge bg-info"><?= h($rosterEntry->class_year) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $rosterEntry->person->id ?? 0]) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="alert alert-info">No roster information available.</div>
            <?php endif; ?>
        </div>

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
                <div class="alert alert-info">No images available for this season.</div>
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
                <div class="alert alert-info">No stories available for this season.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
