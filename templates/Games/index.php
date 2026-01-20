<?php
declare(strict_types=1);
/** @var array<int,\App\Model\Entity\Game> $games */
$this->assign('title', 'Games');
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Games</h1>
        <p class="text-muted mb-0">Men's Basketball game results</p>
    </div>

    <?php if (!empty($games)) : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Season</th>
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
                                <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $game->team_season->id ?? 0]) ?>">
                                    <?= h($game->team_season->season->start ?? '') ?>-<?= h($game->team_season->season->end ?? '') ?>
                                </a>
                            </td>
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
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No games available yet.
        </div>
    <?php endif; ?>
</div>
