<?php $this->assign('title', 'Team Details'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>">Teams</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Team Details</li>
                </ol>
            </nav>
            <h1 class="mb-3">Team Details</h1>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                class="btn btn-secondary mb-3">Back to Teams</a>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                class="btn btn-primary mb-3">Edit Team</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0"><?= h($team->team_name) ?></h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Sport:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->sport ? $team->sport->sport_name : 'N/A') ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Team Name:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->team_name) ?>
                        </div>
                    </div>

                    <?php if (!empty($team->team_description)): ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Description:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->team_description) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Abbreviation:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->abbr) ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Gender:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php
                            $genderLabels = ['M' => 'Male', 'F' => 'Female', 'C' => 'Co-ed'];
                            echo h($genderLabels[$team->gender] ?? $team->gender);
                            ?>
                        </div>
                    </div>

                    <?php if ($team->created_at): ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Created:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= $team->created_at->format('Y-m-d H:i:s') ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($team->updated_at): ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Last Updated:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= $team->updated_at->format('Y-m-d H:i:s') ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                            class="btn btn-primary">Edit Team</a>
                        
                        <?= $this->Form->postLink('Delete Team',
                            ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id],
                            [
                                'class' => 'btn btn-danger',
                                'confirm' => 'Are you sure you want to delete this team? This action cannot be undone.',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Delete this team permanently'
                            ]
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
