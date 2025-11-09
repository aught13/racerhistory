<?php $this->assign('title', isset($stat->id) ? 'Edit Player Stats' : 'Add Player Stats'); ?>
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
                <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>">Player Stats</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= isset($stat->id) ? 'Edit' : 'Add' ?>
            </li>
        </ol>
    </nav>

    <h1 class="mb-3"><?= isset($stat->id) ? 'Edit' : 'Add' ?> Player Stats</h1>

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

    <?= $this->Form->create($stat) ?>
    <?= $this->Form->hidden('game_id') ?>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('team_season_roster_id', [
                        'options' => $teamSeasonRoster,
                        'label' => 'Player',
                        'class' => 'form-select',
                        'required' => true,
                    ]) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('period', [
                        'label' => 'Period (use Z for final)',
                        'class' => 'form-control',
                        'placeholder' => 'Z',
                    ]) ?>
                </div>
            </div>

            <h5 class="mt-3 mb-3">Basic Stats</h5>
            <div class="row">
                <?= $this->Form->hidden('GP', ['value' => $stat->GP ?? 1]) ?>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('GS', [
                        'label' => 'Started?',
                        'type' => 'checkbox',
                        'class' => 'form-check-input',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('MIN', [
                        'label' => 'MIN',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>

            <h5 class="mt-3 mb-3">Shooting</h5>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('FGM', [
                        'label' => 'FGM',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('FGA', [
                        'label' => 'FGA',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('TPM', [
                        'label' => '3PM',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('TPA', [
                        'label' => '3PA',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('FTM', [
                        'label' => 'FTM',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('FTA', [
                        'label' => 'FTA',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>

            <h5 class="mt-3 mb-3">Rebounds & Assists</h5>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('ORB', [
                        'label' => 'ORB',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('DRB', [
                        'label' => 'DRB',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('RB', [
                        'label' => 'RB (Total)',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('AST', [
                        'label' => 'AST',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>

            <h5 class="mt-3 mb-3">Defense & Other</h5>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('STL', [
                        'label' => 'STL',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('BS', [
                        'label' => 'BS (Blocks)',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('BD', [
                        'label' => 'BD (Blocked)',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('TRN', [
                        'label' => 'TRN',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('PF', [
                        'label' => 'PF',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('TF', [
                        'label' => 'TF',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('FD', [
                        'label' => 'FD (Fouls Drawn)',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('PTS', [
                        'label' => 'PTS *',
                        'class' => 'form-control',
                        'type' => 'text',
                        'required' => true,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="mb-3">Season Totals</h5>
            <div class="form-check">
                <?= $this->Form->control('add_to_totals', [
                    'type' => 'checkbox',
                    'label' => 'Update Season Totals',
                    'class' => 'form-check-input',
                    'templates' => [
                        'inputContainer' => '{{content}}',
                        'checkboxWrapper' => '<div class="form-check">{{label}}</div>',
                    ],
                ]) ?>
                <small class="form-text text-muted">
                    Check this box to update season totals with the new values (old values will be subtracted, new values added).
                    Only applies when period is 'Z' (final stats).
                </small>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']) ?>
        <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>" class="btn btn-secondary">Cancel</a>
    </div>
    <?= $this->Form->end() ?>
</div>
