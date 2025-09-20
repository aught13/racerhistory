<?php $this->assign('title', 'Season Details'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>">Seasons</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?= h($season->start . '-' . $season->end) ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="btn-group me-3">
                        <?php if (isset($previousSeason)) : ?>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $previousSeason->id]) ?>"
                                class="btn btn-outline-secondary btn-sm" title="Previous Season: <?= h($previousSeason->start . '-' . $previousSeason->end) ?>">
                                <i class="bi bi-chevron-left"></i> <?= h($previousSeason->start . '-' . $previousSeason->end) ?>
                            </a>
                        <?php else : ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                        <?php endif; ?>

                        <?php if (isset($nextSeason)) : ?>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $nextSeason->id]) ?>"
                                class="btn btn-outline-secondary btn-sm" title="Next Season: <?= h($nextSeason->start . '-' . $nextSeason->end) ?>">
                                <?= h($nextSeason->start . '-' . $nextSeason->end) ?> <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else : ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <h1 class="mb-0">Season: <?= h($season->start . '-' . $season->end) ?></h1>
                </div>
                <div class="btn-group">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'edit', $season->id]) ?>"
                        class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Season
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'add', '?' => ['season_id' => $season->id]]) ?>"
                        class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Add Team Season
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Season Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Start Year:</dt>
                                <dd class="col-sm-8"><?= h($season->start) ?></dd>

                                <dt class="col-sm-4">End Year:</dt>
                                <dd class="col-sm-8"><?= h($season->end) ?></dd>

                                <dt class="col-sm-4">Display:</dt>
                                <dd class="col-sm-8"><?= h($season->start . '-' . $season->end) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($season->created_at instanceof \DateTimeInterface) : ?>
                                        <?= h($season->created_at->format('M j, Y g:i A')) ?>
                                    <?php else : ?>
                                        <?= h($season->created_at) ?>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($season->updated_at instanceof \DateTimeInterface) : ?>
                                        <?= h($season->updated_at->format('M j, Y g:i A')) ?>
                                    <?php else : ?>
                                        <?= h($season->updated_at) ?>
                                    <?php endif; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($season->team_seasons)) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Team Seasons (<?= count($season->team_seasons) ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Team</th>
                                    <th>Semester</th>
                                    <th>League</th>
                                    <th>League Finish</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($season->team_seasons as $teamSeason) : ?>
                                <tr>
                                    <td>
                                        <?php if (isset($teamSeason->team)) : ?>
                                            <?= h($teamSeason->team->team_name) ?>
                                        <?php else : ?>
                                            <em>Team not loaded</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($teamSeason->semester) ?></td>
                                    <td><?= h($teamSeason->league ?: '-') ?></td>
                                    <td><?= h($teamSeason->league_finish ?: '-') ?></td>
                                    <td>
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>"
                                            class="btn btn-sm btn-info">View</a>
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'edit', $teamSeason->id]) ?>"
                                            class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Quick Stats</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Team Seasons:</span>
                        <span class="badge bg-primary"><?= count($season->team_seasons ?? []) ?></span>
                    </div>

                    <?php if (!empty($season->team_seasons)) : ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Unique Teams:</span>
                        <span class="badge bg-secondary">
                            <?php
                            $uniqueTeams = array_unique(array_column($season->team_seasons, 'team_id'));
                            echo count($uniqueTeams);
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title mb-0">Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'edit', $season->id]) ?>"
                            class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Season
                        </a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'add', '?' => ['season_id' => $season->id]]) ?>"
                            class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Add Team Season
                        </a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Seasons
                        </a>

                        <?php
                            $teamSeasonCount = count($season->team_seasons ?? []);
                            $associated = json_encode([
                                ['label' => 'Team Seasons', 'count' => $teamSeasonCount],
                            ]);
                            ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'delete', $season->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'edit', $season->id]) ?>"
                            data-item-type="season" data-associated='<?= $associated ?>'
                            data-form-id="delete-form-season-<?= $season->id ?>">
                            <i class="bi bi-trash"></i> Delete Season
                        </button>
                        <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'delete', $season->id], 'id' => 'delete-form-season-' . $season->id, 'style' => 'display:none']) ?>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'season']) ?>
