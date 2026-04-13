<?php $this->assign('title', 'Team Stats'); ?>
<div class="container py-4">
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
            <li class="breadcrumb-item active" aria-current="page">Team Stats</li>
        </ol>
    </nav>

    <h1 class="mb-3">Team Stats</h1>

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
        <a href="<?= $this->Url->build(['action' => 'edit', $game->id]) ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit Team Stats
        </a>
        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>"
           class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Game
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= h($game->team_season->team->team_name ?? 'Team') ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($teamStats): ?>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th>Offensive Rebounds (ORB)</th>
                                    <td><?= h($teamStats->ORB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Defensive Rebounds (DRB)</th>
                                    <td><?= h($teamStats->DRB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Total Rebounds (RB)</th>
                                    <td><?= h($teamStats->RB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Turnovers (TRN)</th>
                                    <td><?= h($teamStats->TRN ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Technical Fouls (TF)</th>
                                    <td><?= h($teamStats->TF ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Points (PTS)</th>
                                    <td><?= h($teamStats->PTS ?? '-') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No team stats entered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><?= h($game->opponent->opponent_name ?? 'Opponent') ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($opponentStats): ?>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th>Offensive Rebounds (ORB)</th>
                                    <td><?= h($opponentStats->ORB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Defensive Rebounds (DRB)</th>
                                    <td><?= h($opponentStats->DRB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Total Rebounds (RB)</th>
                                    <td><?= h($opponentStats->RB ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Turnovers (TRN)</th>
                                    <td><?= h($opponentStats->TRN ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Technical Fouls (TF)</th>
                                    <td><?= h($opponentStats->TF ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Points (PTS)</th>
                                    <td><?= h($opponentStats->PTS ?? '-') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No opponent stats entered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
