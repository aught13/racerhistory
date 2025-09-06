<?php $this->assign('title', 'Team Season Details'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team Seasons</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php if (isset($teamSeason->team) && isset($teamSeason->season)) : ?>
                            <?= h($teamSeason->team->team_name . ' (' . $teamSeason->season->start . '-' . $teamSeason->season->end . ')') ?>
                        <?php else : ?>
                            Team Season #<?= $teamSeason->id ?>
                        <?php endif; ?>
                    </li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0">
                    <?php if (isset($teamSeason->team) && isset($teamSeason->season)) : ?>
                        <?= h($teamSeason->team->team_name) ?>
                        <small class="text-muted"><?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?></small>
                    <?php else : ?>
                        Team Season Details
                    <?php endif; ?>
                </h1>
                <div class="btn-group">
                    <a
                        href="<?= $this->Url->build([
                            'prefix' => 'Admin',
                            'controller' => 'TeamSeasons',
                            'action' => 'edit',
                            $teamSeason->id
                        ]) ?>"
                        class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Team Season
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Basic Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Team:</dt>
                                <dd class="col-sm-8">
                                    <?php if (isset($teamSeason->team)) : ?>
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $teamSeason->team->id]) ?>">
                                            <?= h($teamSeason->team->team_name) ?>
                                        </a>
                                        <br><small class="text-muted"><?= h($teamSeason->team->abbr) ?></small>
                                    <?php else : ?>
                                        <em>Team not loaded</em>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Season:</dt>
                                <dd class="col-sm-8">
                                    <?php if (isset($teamSeason->season)) : ?>
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $teamSeason->season->id]) ?>">
                                            <?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?>
                                        </a>
                                    <?php else : ?>
                                        <em>Season not loaded</em>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Semester:</dt>
                                <dd class="col-sm-8"><?= h($teamSeason->semester) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($teamSeason->created_at instanceof \DateTimeInterface) : ?>
                                        <?= h($teamSeason->created_at->format('M j, Y g:i A')) ?>
                                    <?php else : ?>
                                        <?= h($teamSeason->created_at) ?>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($teamSeason->updated_at instanceof \DateTimeInterface) : ?>
                                        <?= h($teamSeason->updated_at->format('M j, Y g:i A')) ?>
                                    <?php else : ?>
                                        <?= h($teamSeason->updated_at) ?>
                                    <?php endif; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">League & Competition Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">League:</dt>
                                <dd class="col-sm-8">
                                    <?= h($teamSeason->league ?: '-') ?>
                                    <?php if ($teamSeason->league_abbr) : ?>
                                        <small class="text-muted">(<?= h($teamSeason->league_abbr) ?>)</small>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">League Finish:</dt>
                                <dd class="col-sm-8"><?= h($teamSeason->league_finish ?: '-') ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Tournament Finish:</dt>
                                <dd class="col-sm-7"><?= h($teamSeason->league_torunament_finish ?: '-') ?></dd>

                                <dt class="col-sm-5">Last Post Game:</dt>
                                <dd class="col-sm-7"><?= h($teamSeason->last_post_game ?: '-') ?></dd>
                            </dl>
                        </div>
                    </div>

                    <?php if ($teamSeason->team_season_notes) : ?>
                    <div class="mt-3">
                        <h5>Season Notes</h5>
                        <p class="text-muted"><?= h($teamSeason->team_season_notes) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($teamSeason->team_season_image) : ?>
                    <div class="mt-3">
                        <h5>Season Image</h5>
                        <div class="mb-2">
                            <?= $this->element('team_season_image', ['teamSeason' => $teamSeason, 'size' => 'medium']) ?>
                        </div>
                        <p class="text-muted small mb-0">Stored ID/Value: <?= h($teamSeason->team_season_image) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($teamSeason->team_season_preview || $teamSeason->team_season_recap) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Season Content</h3>
                </div>
                <div class="card-body">
                    <?php if ($teamSeason->team_season_preview) : ?>
                    <div class="mb-4">
                        <h5>Season Preview</h5>
                        <div class="p-3 bg-light rounded team-season-preview">
                            <?php
                                $html = (string)$teamSeason->team_season_preview;
                                // Strip script/style tags for safety
                                $clean = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
                                echo $clean; // Already sanitized minimally; assumes trusted admin input
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($teamSeason->team_season_recap) : ?>
                    <div>
                        <h5>Season Recap</h5>
                        <div class="p-3 bg-light rounded team-season-recap">
                            <?php
                                $html = (string)$teamSeason->team_season_recap;
                                $clean = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
                                echo $clean;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'edit', $teamSeason->id]) ?>"
                            class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Team Season
                        </a>

                        <?php if (isset($teamSeason->team)) : ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $teamSeason->team->id]) ?>"
                            class="btn btn-outline-info">
                            <i class="bi bi-eye"></i> View Team
                        </a>
                        <?php endif; ?>

                        <?php if (isset($teamSeason->season)) : ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $teamSeason->season->id]) ?>"
                            class="btn btn-outline-info">
                            <i class="bi bi-eye"></i> View Season
                        </a>
                        <?php endif; ?>

                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Team Seasons
                        </a>

                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'delete', $teamSeason->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'edit', $teamSeason->id]) ?>"
                            data-item-type="team season" data-associated='[]'
                            data-form-id="delete-form-team-season-<?= $teamSeason->id ?>">
                            <i class="bi bi-trash"></i> Delete Team Season
                        </button>
                        <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'delete', $teamSeason->id], 'id' => 'delete-form-team-season-' . $teamSeason->id, 'style' => 'display:none']) ?>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>

            <?php if (isset($teamSeason->team) && isset($teamSeason->team->sport)) : ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title mb-0">Related Information</h4>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Sport:</dt>
                        <dd class="col-sm-8">
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $teamSeason->team->sport->id]) ?>">
                                <?= h($teamSeason->team->sport->sport_name) ?>
                            </a>
                        </dd>

                        <dt class="col-sm-4">Team Gender:</dt>
                        <dd class="col-sm-8">
                            <?php
                            $genderDisplay = match ($teamSeason->team->gender) {
                                'M' => 'Male',
                                'F' => 'Female',
                                'C' => 'Co-ed',
                                default => $teamSeason->team->gender
                            };
    ?>
                            <?= h($genderDisplay) ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team season']) ?>
