<?php $this->assign('title', 'Edit Team Season Stats'); ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team Seasons</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>">
                    <?= h($teamSeason->team->team_name ?? 'Team') ?> - <?= h($teamSeason->season->name ?? 'Season') ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit Team Stats</li>
        </ol>
    </nav>

    <h1 class="mb-3">Edit Team Season Stats</h1>

    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($stat, ['novalidate' => true]) ?>
            <?= $this->Form->hidden('team_season_id') ?>

            <h5 class="mt-3 mb-3">Basic Stats</h5>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('GP', [
                        'label' => 'GP (Games Played)',
                        'class' => 'form-control',
                        'type' => 'text',
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

            <h5 class="mt-3 mb-3">Shooting Stats</h5>
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
                    <?= $this->Form->control('PTS', [
                        'label' => 'PTS',
                        'class' => 'form-control',
                        'type' => 'text',
                    ]) ?>
                </div>
            </div>

            <div class="mt-3">
                <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link(__('Cancel'), ['controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
