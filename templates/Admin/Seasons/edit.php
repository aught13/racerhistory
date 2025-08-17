<?php $this->assign('title', 'Edit Season'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>">Seasons</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $season->id]) ?>"><?= h($season->start . '-' . $season->end) ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h1 class="mb-3">Edit Season: <?= h($season->start . '-' . $season->end) ?></h1>
            <p class="text-muted">
                Update season information. Be careful when changing season years as this affects all associated team seasons.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Season Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($season, ['novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="start-year" class="form-label">Start Year *</label>
                        <?= $this->Form->control('start', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'start-year',
                            'placeholder' => 'e.g., 2023'
                        ]) ?>
                        <div class="form-text">The starting year of the season (e.g., 2023 for 2023-2024 season).</div>
                    </div>

                    <div class="mb-3">
                        <label for="end-year" class="form-label">End Year *</label>
                        <?= $this->Form->control('end', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'end-year',
                            'placeholder' => 'e.g., 2024'
                        ]) ?>
                        <div class="form-text">The ending year of the season (e.g., 2024 for 2023-2024 season).</div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Update Season'), [
                            'type' => 'submit',
                            'class' => 'btn btn-primary'
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $season->id]) ?>"
                            class="btn btn-secondary">Cancel</a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>"
                            class="btn btn-outline-secondary">Back to List</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Associated Records</h4>
                </div>
                <div class="card-body">
                    <?php $teamSeasonCount = isset($season->team_seasons) ? count($season->team_seasons) : 0; ?>

                    <div class="mb-3">
                        <strong>Team Seasons:</strong>
                        <span class="badge bg-secondary"><?= $teamSeasonCount ?></span>
                    </div>

                    <?php if ($teamSeasonCount > 0) : ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> This season has <?= $teamSeasonCount ?> associated team season(s).
                        Changing the season years will affect all related records.
                    </div>
                    <?php endif; ?>

                    <h5>Season Guidelines</h5>
                    <ul class="small text-muted">
                        <li>Both start and end years are required</li>
                        <li>Years should be sequential</li>
                        <li>Changes affect all team seasons</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title mb-0">Record Information</h4>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-1">
                        <strong>Created:</strong>
                        <?php if ($season->created_at instanceof \DateTimeInterface) : ?>
                            <?= h($season->created_at->format('M j, Y g:i A')) ?>
                        <?php else : ?>
                            <?= h($season->created_at) ?>
                        <?php endif; ?>
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Last Updated:</strong>
                        <?php if ($season->updated_at instanceof \DateTimeInterface) : ?>
                            <?= h($season->updated_at->format('M j, Y g:i A')) ?>
                        <?php else : ?>
                            <?= h($season->updated_at) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
