<?php
declare(strict_types=1);
/** @var \App\Model\Entity\TeamSeason[] $teamSeasons */
$this->assign('title', 'Seasons - Men\'s Basketball');
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Seasons</h1>
        <p class="text-muted mb-0">Men's Basketball team seasons</p>
    </div>

    <?php if (!empty($teamSeasons)) : ?>
        <div class="row g-4">
            <?php foreach ($teamSeasons as $teamSeason) : ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($teamSeason->hero_image_id)) : ?>
                            <img src="/images/serve/<?= h($teamSeason->hero_image_id) ?>?w=400&h=250&fit=cover"
                                 class="card-img-top"
                                 alt="<?= h($teamSeason->team->team_name ?? 'Team') ?> <?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?>"
                                 loading="lazy">
                        <?php else : ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 250px;">
                                <i class="bi bi-trophy text-white" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?>
                            </h5>
                            <p class="card-text">
                                <strong><?= h($teamSeason->team->team_name ?? 'Team') ?></strong>
                            </p>
                            <?php if (!empty($teamSeason->league)) : ?>
                                <p class="card-text text-muted mb-1">
                                    <small><?= h($teamSeason->league) ?>
                                        <?php if (!empty($teamSeason->league_finish)) : ?>
                                            - <?= h($teamSeason->league_finish) ?>
                                        <?php endif; ?>
                                    </small>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($teamSeason->record_display)) : ?>
                                <p class="card-text">
                                    <span class="badge bg-primary"><?= h($teamSeason->record_display) ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $teamSeason->id]) ?>"
                               class="btn btn-sm btn-outline-primary w-100">
                                View Season Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No seasons available yet.
        </div>
    <?php endif; ?>
</div>
