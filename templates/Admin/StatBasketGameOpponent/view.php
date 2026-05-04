<?php
/**
 * @var \App\View\AppView $this
 * @var object $stats
 * @var \App\Model\Entity\Game $game
 */
?>
<?php $this->assign('title', 'Opponent Player Stats'); ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'teamSeasons', 'action' => 'view', $game->team_season_id]) ?>">
                    Team Season
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">Game Details</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Opponent Stats</li>
        </ol>
    </nav>

    <h1 class="mb-3">Opponent Player Stats</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <?= h($game->team_season->team->team_name ?? 'Team') ?> vs
                <?= h($game->opponent->opponent_name ?? 'Opponent') ?>
                <span class="text-muted">
                    (<?= $game->game_date ? $game->game_date->format('M j, Y') : '' ?>)
                </span>
            </h5>
        </div>
    </div>

    <div class="mb-3">
        <a href="<?= $this->Url->build(['action' => 'add', $game->id]) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Opponent Player
        </a>
        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>"
           class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Game
        </a>
    </div>

    <?php if ($stats->count() > 0) : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Jersey</th>
                        <th>Pos</th>
                        <th>Period</th>
                        <th>MIN</th>
                        <th>FGM</th>
                        <th>FGA</th>
                        <th>3PM</th>
                        <th>3PA</th>
                        <th>FTM</th>
                        <th>FTA</th>
                        <th>ORB</th>
                        <th>DRB</th>
                        <th>RB</th>
                        <th>AST</th>
                        <th>STL</th>
                        <th>BS</th>
                        <th>BD</th>
                        <th>TRN</th>
                        <th>PF</th>
                        <th>FD</th>
                        <th>PTS</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat) : ?>
                        <tr>
                            <td><?= h($stat->name ?? '') ?></td>
                            <td><?= h($stat->jersey ?? '') ?></td>
                            <td><?= h($stat->position ?? '') ?></td>
                            <td><?= h($stat->period ?? 'Z') ?></td>
                            <td><?= h($stat->MIN ?? '') ?></td>
                            <td><?= h($stat->FGM ?? '') ?></td>
                            <td><?= h($stat->FGA ?? '') ?></td>
                            <td><?= h($stat->TPM ?? '') ?></td>
                            <td><?= h($stat->TPA ?? '') ?></td>
                            <td><?= h($stat->FTM ?? '') ?></td>
                            <td><?= h($stat->FTA ?? '') ?></td>
                            <td><?= h($stat->ORB ?? '') ?></td>
                            <td><?= h($stat->DRB ?? '') ?></td>
                            <td><?= h($stat->RB ?? '') ?></td>
                            <td><?= h($stat->AST ?? '') ?></td>
                            <td><?= h($stat->STL ?? '') ?></td>
                            <td><?= h($stat->BS ?? '') ?></td>
                            <td><?= h($stat->BD ?? '') ?></td>
                            <td><?= h($stat->TRN ?? '') ?></td>
                            <td><?= h($stat->PF ?? '') ?></td>
                            <td><?= h($stat->FD ?? '') ?></td>
                            <td><?= h($stat->PTS ?? '') ?></td>
                            <td class="text-end">
                                <a href="<?= $this->Url->build(['action' => 'edit', $stat->id]) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash"></i>',
                                    ['action' => 'delete', $stat->id],
                                    [
                                        'confirm' => __('Are you sure you want to delete this opponent player stat?'),
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'escape' => false,
                                    ],
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No opponent player stats have been entered for this game yet.
        </div>
    <?php endif; ?>
</div>
