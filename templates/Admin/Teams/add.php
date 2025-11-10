<?php $this->assign('title', 'Add Team'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>">Teams</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Add Team</li>
                </ol>
            </nav>
            <h1 class="mb-3">Add New Team</h1>
            <p class="text-muted">
                Create a new team. All teams must be assigned to a sport and have a gender classification.
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
                        <label for="sport-id" class="form-label">Sport *</label>
                        <div class="input-group d-flex align-items-stretch">
                            <div class="flex-grow-1">
                                <?= $this->Form->control('sport_id', [
                                    'type' => 'select',
                                    'options' => $sports,
                                    'empty' => 'Select a Sport',
                                    // Use Bootstrap 5 select sizing so it matches buttons in input-group
                                    'class' => 'form-select h-100',
                                    'label' => false,
                                    'required' => true,
                                    'id' => 'sport-id'
                                ]) ?>
                            </div>
                            <button type="button" class="btn btn-success h-100 border-0" data-bs-toggle="modal"
                                data-bs-target="#add-sport-modal" title="Add New Sport" aria-label="Add new sport">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                        </div>
                        <div class="form-text">Select the sport for this team or add a new sport.</div>
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
                        <div class="form-text">Full official name including institution and sport (maximum 240
                            characters).</div>
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
                        <div class="form-text">Short abbreviation for compact display (maximum 5 characters, e.g., "WBB"
                            for Women's Basketball).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_nickname', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Team Nickname/Mascot *'],
                            'placeholder' => 'Enter team nickname (e.g., Racers)',
                            'maxlength' => 30,
                            'required' => true
                        ]) ?>
                        <div class="form-text">Team mascot or nickname (maximum 30 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_scorebug', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Scorebug Name *'],
                            'placeholder' => 'Enter scorebug name (e.g., MURRAY)',
                            'maxlength' => 6,
                            'required' => true
                        ]) ?>
                        <div class="form-text">Shortened name for score display (maximum 6 characters).</div>
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
                        <div class="form-text">Specify whether this is a Male, Female, or Co-ed team for proper
                            competition classification.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Save Team'), [
                            'class' => 'btn btn-success'
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Help</h5>
                </div>
                <div class="card-body">
                    <h6>Required Fields</h6>
                    <ul class="list-unstyled">
                        <li><strong>Sport:</strong> Select the sport this team plays</li>
                        <li><strong>Team Name:</strong> Full name of the team</li>
                        <li><strong>Abbreviation:</strong> Short code for the team (max 5 chars)</li>
                        <li><strong>Nickname:</strong> Team mascot or nickname (max 30 chars)</li>
                        <li><strong>Scorebug:</strong> Short name for score display (max 6 chars)</li>
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

<!-- Hidden form to generate FormProtection tokens for AJAX sport creation -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'ajaxAdd'],
        'id' => 'hidden-sport-form'
    ]) ?>
    <?= $this->Form->control('sport_name', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?php
// Add Sport Popup Form
echo $this->element('Admin/popup_form', [
    'popupId' => 'add-sport-modal',
    'title' => 'Add New Sport',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'sport-id',
    'successCallback' => 'handleSportAdded',
    'fields' => [
        [
            'name' => 'sport_name',
            'type' => 'text',
            'label' => 'Sport Name',
            'placeholder' => 'Enter sport name (e.g., Basketball)',
            'required' => true,
            'attributes' => [
                'maxlength' => 162
            ]
        ]
    ]
]);
?>

<script>
function handleSportAdded(data) {
    // Custom callback for when a sport is successfully added
    console.log('Sport added successfully:', data);

    // You can add additional logic here if needed
    // For example, updating other UI elements or analytics tracking
}
</script>
