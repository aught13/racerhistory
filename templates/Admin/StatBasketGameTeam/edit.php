<?php $this->assign('title', 'Edit Team Stats'); ?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">
                    Game Details
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>">Team Stats</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <h1 class="mb-3">Edit Team Stats</h1>

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

    <?= $this->Form->create() ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= h($game->team_season->team->team_name ?? 'Team') ?> Stats</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->control('team.ORB', [
                        'label' => 'Offensive Rebounds (ORB)',
                        'class' => 'form-control',
                        'value' => $teamStats->ORB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('team.DRB', [
                        'label' => 'Defensive Rebounds (DRB)',
                        'class' => 'form-control',
                        'value' => $teamStats->DRB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('team.RB', [
                        'label' => 'Total Rebounds (RB)',
                        'class' => 'form-control',
                        'value' => $teamStats->RB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('team.TRN', [
                        'label' => 'Turnovers (TRN)',
                        'class' => 'form-control',
                        'value' => $teamStats->TRN ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('team.TF', [
                        'label' => 'Technical Fouls (TF)',
                        'class' => 'form-control',
                        'value' => $teamStats->TF ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('team.PTS', [
                        'label' => 'Points (PTS)',
                        'class' => 'form-control',
                        'value' => $teamStats->PTS ?? '',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><?= h($game->opponent->opponent_name ?? 'Opponent') ?> Stats</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->control('opponent.ORB', [
                        'label' => 'Offensive Rebounds (ORB)',
                        'class' => 'form-control',
                        'value' => $opponentStats->ORB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('opponent.DRB', [
                        'label' => 'Defensive Rebounds (DRB)',
                        'class' => 'form-control',
                        'value' => $opponentStats->DRB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('opponent.RB', [
                        'label' => 'Total Rebounds (RB)',
                        'class' => 'form-control',
                        'value' => $opponentStats->RB ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('opponent.TRN', [
                        'label' => 'Turnovers (TRN)',
                        'class' => 'form-control',
                        'value' => $opponentStats->TRN ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('opponent.TF', [
                        'label' => 'Technical Fouls (TF)',
                        'class' => 'form-control',
                        'value' => $opponentStats->TF ?? '',
                        'type' => 'text',
                    ]) ?>
                    <?= $this->Form->control('opponent.PTS', [
                        'label' => 'Points (PTS)',
                        'class' => 'form-control',
                        'value' => $opponentStats->PTS ?? '',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <?= $this->Form->button(__('Save All'), ['class' => 'btn btn-primary']) ?>
        <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>" class="btn btn-secondary">Cancel</a>
    </div>
    <?= $this->Form->end() ?>
</div>
