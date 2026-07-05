<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var array<int,\App\Model\Entity\StatBasketSeasonPerson> $playerStats
 * @var \App\View\AppView $this
 */
$this->assign('title', ($teamSeason->season->start ?? '') . '-' . ($teamSeason->season->end ?? '') . ' Stats');
?>
<div class="container py-4" data-controller="stats-page">

    <h1 class="display-6 mb-4">
        <?= h($teamSeason->team->team_name ?? 'Team') ?>
        <small class="text-muted"><?= h($teamSeason->season->start ?? '') ?>-<?= h($teamSeason->season->end ?? '') ?></small>
    </h1>

    <?php if (!empty($playerStats)) : ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Player Season Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Player</th>
                                <th>GP</th>
                                <th>MIN</th>
                                <th>PTS</th>
                                <th>PPG</th>
                                <th>REB</th>
                                <th>RPG</th>
                                <th>AST</th>
                                <th>APG</th>
                                <th>FG%</th>
                                <th>3PT%</th>
                                <th>FT%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($playerStats as $stat) : ?>
                                <tr>
                                    <td>
                                        <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $stat->person->id ?? 0]) ?>">
                                            <?= h($stat->person->first_name ?? '') ?> <?= h($stat->person->last_name ?? '') ?>
                                        </a>
                                    </td>
                                    <td><?= h($stat->gp ?? 0) ?></td>
                                    <td><?= h($stat->min ?? 0) ?></td>
                                    <td><strong><?= h($stat->pts ?? 0) ?></strong></td>
                                    <td><?= number_format($stat->ppg ?? 0, 1) ?></td>
                                    <td><?= h($stat->reb ?? 0) ?></td>
                                    <td><?= number_format($stat->rpg ?? 0, 1) ?></td>
                                    <td><?= h($stat->ast ?? 0) ?></td>
                                    <td><?= number_format($stat->apg ?? 0, 1) ?></td>
                                    <td>
                                        <?php if (!empty($stat->fga) && $stat->fga > 0) : ?>
                                            <?= number_format($stat->fgm / $stat->fga * 100, 1) ?>%
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($stat->fg3a) && $stat->fg3a > 0) : ?>
                                            <?= number_format($stat->fg3m / $stat->fg3a * 100, 1) ?>%
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($stat->fta) && $stat->fta > 0) : ?>
                                            <?= number_format($stat->ftm / $stat->fta * 100, 1) ?>%
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No player statistics available for this season.
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $teamSeason->id]) ?>"
           class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i> Back to Season Details
        </a>
    </div>
</div>
