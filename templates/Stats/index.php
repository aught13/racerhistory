<?php
declare(strict_types=1);
/** @var array<int,\App\Model\Entity\TeamSeason> $teamSeasons */
$this->assign('title', 'Statistics');
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Statistics</h1>
        <p class="text-muted mb-0">Men's Basketball statistics by season</p>
    </div>

    <?php if (!empty($teamSeasons)) : ?>
        <div class="list-group">
            <?php foreach ($teamSeasons as $teamSeason) : ?>
                <a href="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'season', $teamSeason->id]) ?>"
                   class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?>
                                <?= h($teamSeason->team->team_name ?? '') ?>
                            </h5>
                            <?php if (!empty($teamSeason->record_display)) : ?>
                                <p class="mb-1 text-muted"><?= h($teamSeason->record_display) ?></p>
                            <?php endif; ?>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No statistics available yet.
        </div>
    <?php endif; ?>
</div>
