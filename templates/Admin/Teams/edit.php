<?php $this->assign('title', 'Edit Team'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>">Teams</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>">Team Details</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h1 class="mb-3">Edit Team: <?= h($team->team_name) ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Team Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($team, ['novalidate' => true]) ?>
                    
                    <div class="mb-3">
                        <?= $this->Form->control('sport_id', [
                            'type' => 'select',
                            'options' => $sports,
                            'empty' => 'Select a Sport',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Sport *'],
                            'required' => true
                        ]) ?>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_name', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Team Name *'],
                            'placeholder' => 'Enter team name',
                            'maxlength' => 162,
                            'required' => true
                        ]) ?>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_description', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Description'],
                            'placeholder' => 'Enter team description (optional)',
                            'rows' => 3,
                            'maxlength' => 240
                        ]) ?>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('abbr', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Abbreviation *'],
                            'placeholder' => 'Enter team abbreviation',
                            'maxlength' => 5,
                            'required' => true
                        ]) ?>
                        <div class="form-text">Maximum 5 characters (e.g., "LAK" for Lakers)</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('gender', [
                            'type' => 'select',
                            'options' => [
                                'M' => 'Male',
                                'F' => 'Female',
                                'C' => 'Co-ed'
                            ],
                            'empty' => 'Select Gender',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Gender *'],
                            'required' => true
                        ]) ?>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Update Team'), [
                            'class' => 'btn btn-success'
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>"
                            class="btn btn-secondary">Cancel</a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                            class="btn btn-outline-secondary">Back to List</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>"
                            class="btn btn-info">View Team</a>
                        
                        <?= $this->Form->postLink('Delete Team',
                            ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id],
                            [
                                'class' => 'btn btn-danger',
                                'confirm' => 'Are you sure you want to delete this team? This action cannot be undone.'
                            ]
                        ) ?>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Help</h5>
                </div>
                <div class="card-body">
                    <h6>Required Fields</h6>
                    <ul class="list-unstyled">
                        <li><strong>Sport:</strong> Select the sport this team plays</li>
                        <li><strong>Team Name:</strong> Full name of the team</li>
                        <li><strong>Abbreviation:</strong> Short code for the team (max 5 chars)</li>
                        <li><strong>Gender:</strong> Team gender classification</li>
                    </ul>
                    
                    <h6 class="mt-3">Optional Fields</h6>
                    <ul class="list-unstyled">
                        <li><strong>Description:</strong> Additional information about the team</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
