<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $sports
 * @var \App\Model\Entity\Team $team
 */
?>
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

    <div class="row" data-controller="team-name-preview">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Team Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($team, ['novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="sport-key" class="form-label">Sport *</label>
                        <?= $this->Form->control('sport_key', [
                            'type' => 'select',
                            'options' => $sports,
                            'empty' => 'Select a Sport',
                            'class' => 'form-select',
                            'label' => false,
                            'required' => true,
                            'id' => 'sport-key',
                        ]) ?>
                        <div class="form-text">Select the sport for this team.</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_name', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Base Team Name *'],
                            'placeholder' => 'Enter a base name (e.g., Basketball)',
                            'maxlength' => 162,
                            'required' => true,
                            'data-team-name-preview-target' => 'teamName',
                            'data-action' => 'input->team-name-preview#update change->team-name-preview#update',
                        ]) ?>
                        <div class="form-text">Base team/category phrase included in every display name (maximum 162 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_description', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Organization / Name Prefix'],
                            'placeholder' => 'Enter organization or prefix (e.g., Murray State)',
                            'rows' => 3,
                            'maxlength' => 240,
                            'data-team-name-preview-target' => 'teamDescription',
                            'data-action' => 'input->team-name-preview#update change->team-name-preview#update',
                        ]) ?>
                        <div class="form-text">Optional leading organization or descriptor used in Full Team Name (maximum 240 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('abbr', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Abbreviation *'],
                            'placeholder' => 'Enter team abbreviation (e.g., WBB)',
                            'maxlength' => 5,
                            'required' => true,
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
                            'required' => true,
                            'data-team-name-preview-target' => 'teamNickname',
                            'data-action' => 'input->team-name-preview#update change->team-name-preview#update',
                        ]) ?>
                        <div class="form-text">Mascot or nickname included in Team Name and Full Team Name (maximum 30 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('team_scorebug', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Scorebug Name *'],
                            'placeholder' => 'Enter scorebug name (e.g., MURRAY)',
                            'maxlength' => 6,
                            'required' => true,
                        ]) ?>
                        <div class="form-text">Shortened name for score display (maximum 6 characters).</div>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('gender', [
                            'type' => 'select',
                            'options' => [
                                'M' => 'Male',
                                'F' => 'Female',
                                'C' => 'Co-ed',
                            ],
                            'empty' => 'Select Gender Classification',
                            'class' => 'form-control',
                            'label' => ['class' => 'form-label', 'text' => 'Gender Classification *'],
                            'required' => true,
                            'data-team-name-preview-target' => 'gender',
                            'data-action' => 'input->team-name-preview#update change->team-name-preview#update',
                        ]) ?>
                        <div class="form-text">Classifies the team; Male/Female may appear in display names, while Co-ed is omitted.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Save Team'), [
                            'class' => 'btn btn-success',
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Display Previews</h5>
                </div>
                <div class="card-body">
                    <p><strong>Full Team Name:</strong><br>
                        <span data-team-name-preview-target="fullTeamName"><?= h($team->getFullTeamDisplayName()) ?></span>
                    </p>
                    <p><strong>Team Name (default):</strong><br>
                        <span data-team-name-preview-target="defaultTeamName"><?= h($team->getDisplayName()) ?></span>
                    </p>
                    <p class="mb-0"><strong>Team:</strong><br>
                        <span data-team-name-preview-target="team"><?= h($team->getTeamDisplayName()) ?></span>
                    </p>
                    <small class="text-muted">
                        Older records may contain complete names in these fields. Update them to their component meanings; previews reflect your edits.
                    </small>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Help</h5>
                </div>
                <div class="card-body">
                    <h6>Required Fields</h6>
                    <ul class="list-unstyled">
                        <li><strong>Sport:</strong> Select the sport this team plays</li>
                        <li><strong>Base Team Name:</strong> Name/category included in each display value</li>
                        <li><strong>Abbreviation:</strong> Short code for the team (max 5 chars)</li>
                        <li><strong>Nickname:</strong> Team mascot or nickname (max 30 chars)</li>
                        <li><strong>Scorebug:</strong> Short name for score display (max 6 chars)</li>
                        <li><strong>Gender:</strong> Team classification; Co-ed is not shown in display values</li>
                    </ul>

                    <h6 class="mt-3">Optional Fields</h6>
                    <ul class="list-unstyled">
                        <li><strong>Organization / Prefix:</strong> Optional first part of Full Team Name</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
