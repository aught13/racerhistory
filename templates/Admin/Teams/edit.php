<?php

$this->assign('title', 'Edit Team'); ?>
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
            <p class="text-muted">
                Update team information. All teams must be assigned to a sport and have a gender classification.
            </p>
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
                        <div class="form-text">Select the sport category this team will compete in.</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_name', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Team Name *'],
                            'placeholder' => 'Enter team name (e.g., Women\'s Basketball)',
                            'maxlength' => 162,
                            'required' => true
                        ]) ?>
                        <div class="form-text">Short display name of the team (maximum 162 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_description', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Long Name'],
                            'placeholder' => 'Enter full team name (e.g., Murray St Racers Women\'s Basketball)',
                            'rows' => 3,
                            'maxlength' => 240
                        ]) ?>
                        <div class="form-text">Full official name including institution and sport (maximum 240 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('abbr', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Abbreviation *'],
                            'placeholder' => 'Enter team abbreviation (e.g., WBB)',
                            'maxlength' => 5,
                            'required' => true
                        ]) ?>
                        <div class="form-text">Short abbreviation for compact display (maximum 5 characters, e.g., "WBB" for Women's Basketball).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('gender', [
                            'type' => 'select',
                            'options' => [
                                'M' => 'Male',
                                'F' => 'Female',
                                'C' => 'Co-ed'
                            ],
                            'empty' => 'Select Gender Classification',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Gender Classification *'],
                            'required' => true
                        ]) ?>
                        <div class="form-text">Specify whether this is a Male, Female, or Co-ed team for proper competition classification.</div>
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

                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id]) ?>"
                                data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                data-item-type="team">
                            Delete Team
                        </button>
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

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team']) ?>
