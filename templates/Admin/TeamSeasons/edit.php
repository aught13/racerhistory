<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $seasonsList
 * @var mixed $teams
 * @var \App\Model\Entity\TeamSeason $teamSeason
 */
?>
<?php
$initialImageId = !empty($teamSeason->team_season_image) ? (string)(int)$teamSeason->team_season_image : '';
$initialPreviewUrl = $initialImageId !== '' ? $this->ImageServe->url((int)$initialImageId, ['variant' => 'hero']) : '';
?>
<?php $this->assign('title', 'Edit Team Season'); ?>
<div class="container py-4" data-controller="team-season-form" data-team-season-form-existing-image-id-value="<?= h($initialImageId) ?>" data-team-season-form-existing-preview-url-value="<?= h($initialPreviewUrl) ?>" data-team-season-form-upload-url-value="/admin/images/upload">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team Seasons</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>">
                            <?php if (isset($teamSeason->team) && isset($teamSeason->season)) : ?>
                                <?= h($teamSeason->team->team_name . ' (' . $teamSeason->season->start . '-' . $teamSeason->season->end . ')') ?>
                            <?php else : ?>
                                Team Season #<?= $teamSeason->id ?>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h1 class="mb-3">Edit Team Season</h1>
            <p class="text-muted">
                Update team season information and competition details.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Team Season Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($teamSeason, ['novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="team-id" class="form-label">Team *</label>
                        <?= $this->Form->control('team_id', [
                            'type' => 'select',
                            'options' => $teams,
                            'empty' => 'Select a Team',
                            'class' => 'form-select',
                            'label' => false,
                            'required' => true,
                            'id' => 'team-id',
                        ]) ?>
                        <div class="form-text">The team for this season participation.</div>
                    </div>

                    <div class="mb-3">
                        <label for="season-id" class="form-label">Season *</label>
                        <?= $this->Form->control('season_id', [
                            'type' => 'select',
                            'options' => $seasonsList,
                            'empty' => 'Select a Season',
                            'class' => 'form-select',
                            'label' => false,
                            'required' => true,
                            'id' => 'season-id',
                        ]) ?>
                        <div class="form-text">The season for this team participation.</div>
                    </div>

                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester *</label>
                        <?= $this->Form->control('semester', [
                            'type' => 'number',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'semester',
                            'min' => 1,
                            'max' => 4,
                        ]) ?>
                        <div class="form-text">Semester number (1-4) for this team season.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="league" class="form-label">League</label>
                                <?= $this->Form->control('league', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league',
                                    'maxlength' => 240,
                                ]) ?>
                                <div class="form-text">League or conference name (max 240 characters).</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="league-abbr" class="form-label">League Abbreviation</label>
                                <?= $this->Form->control('league_abbr', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-abbr',
                                    'maxlength' => 10,
                                ]) ?>
                                <div class="form-text">League abbreviation (max 10 characters).</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="league-finish" class="form-label">League Finish</label>
                                <?= $this->Form->control('league_finish', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-finish',
                                    'maxlength' => 240,
                                ]) ?>
                                <div class="form-text">Final position or record in league play.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="league-tournament-finish" class="form-label">League Tournament Finish</label>
                                <?= $this->Form->control('league_torunament_finish', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-tournament-finish',
                                    'maxlength' => 240,
                                ]) ?>
                                <div class="form-text">Tournament finish position.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="last-post-game" class="form-label">Last Post Game</label>
                        <?= $this->Form->control('last_post_game', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'last-post-game',
                            'maxlength' => 240,
                        ]) ?>
                        <div class="form-text">Information about the final game or post-season activities.</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-notes" class="form-label">Season Notes</label>
                        <?= $this->Form->control('team_season_notes', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-notes',
                            'maxlength' => 240,
                        ]) ?>
                        <div class="form-text">General notes about this team season (max 240 characters).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Season Image</label>
                        <div class="row">
                            <div class="col-md-8">
                                <?= $this->Form->control('team_season_image', [
                                    'class' => 'form-control',
                                    'label' => false,
                                    'placeholder' => 'Image ID',
                                    'id' => 'team-season-image-field',
                                    'data-team-season-form-target' => 'imageField',
                                ]) ?>
                            </div>
                            <div class="col-md-4 d-grid gap-2">
                                <button type="button" class="btn btn-secondary form-control" data-bs-toggle="modal" data-bs-target="#team-season-image-selector">
                                    Select/Upload Image
                                </button>
                                <a
                                    id="team-season-hero-variant-btn"
                                    data-team-season-form-target="heroVariantButton"
                                    class="btn btn-outline-primary form-control"
                                    href="#"
                                    target="_blank"
                                    rel="noopener"
                                    style="display: none;"
                                >Edit Hero Crop</a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div id="team-season-image-preview" class="mt-2" style="display: none;" data-team-season-form-target="imagePreview">
                                    <img src="" alt="Season Image Preview" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Update Team Season'), [
                            'type' => 'submit',
                            'class' => 'btn btn-primary',
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>"
                            class="btn btn-secondary">Cancel</a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>"
                            class="btn btn-outline-secondary">Back to List</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Current Information</h4>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Team:</dt>
                        <dd class="col-sm-8">
                            <?php if (isset($teamSeason->team)) : ?>
                                <?= h($teamSeason->team->team_name) ?>
                                <br><small class="text-muted"><?= h($teamSeason->team->abbr) ?></small>
                            <?php else : ?>
                                <em>Team not loaded</em>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4">Season:</dt>
                        <dd class="col-sm-8">
                            <?php if (isset($teamSeason->season)) : ?>
                                <?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?>
                            <?php else : ?>
                                <em>Season not loaded</em>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4">Semester:</dt>
                        <dd class="col-sm-8"><?= h($teamSeason->semester) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title mb-0">Record Information</h4>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-1">
                        <strong>Created:</strong>
                        <?php if ($teamSeason->created_at instanceof DateTimeInterface) : ?>
                            <?= h($teamSeason->created_at->format('M j, Y g:i A')) ?>
                        <?php else : ?>
                            <?= h($teamSeason->created_at) ?>
                        <?php endif; ?>
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Last Updated:</strong>
                        <?php if ($teamSeason->updated_at instanceof DateTimeInterface) : ?>
                            <?= h($teamSeason->updated_at->format('M j, Y g:i A')) ?>
                        <?php else : ?>
                            <?= h($teamSeason->updated_at) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

                <?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team season']) ?>

                <?php
                // Image selector modal for team season images
                $modalId = 'team-season-image-selector';
                $targetFieldId = 'team-season-image-field';
                $tagFilter = null; // Show full library; legacy season images may not be teamseason-tagged.
                $uploadContext = ['type' => 'teamseason', 'id' => $teamSeason->id];
                $aspectRatio = 16 / 9; // Widescreen aspect ratio (16:9, covers 4:3, 5:4 formats)
                echo $this->element('Admin/image_selector_modal', compact('modalId', 'targetFieldId', 'tagFilter', 'uploadContext', 'aspectRatio'));
                ?>

                <?php
                echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
                echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
                ?>
