<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $seasonsList
 * @var mixed $teams
 * @var \App\Model\Entity\TeamSeason $teamSeason
 */
?>
<?php $this->assign('title', 'Edit Team Season'); ?>
<div class="container py-4">
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
                                ]) ?>
                            </div>
                            <div class="col-md-4 d-grid gap-2">
                                <button type="button" class="btn btn-secondary form-control" data-bs-toggle="modal" data-bs-target="#team-season-image-selector">
                                    Select/Upload Image
                                </button>
                                <a
                                    id="team-season-hero-variant-btn"
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
                                <div id="team-season-image-preview" class="mt-2" style="display: none;">
                                    <img src="" alt="Season Image Preview" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-preview" class="form-label">Season Preview</label>
                        <?= $this->Form->control('team_season_preview', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-preview',
                            'rows' => 8,
                            'templates' => [
                                'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                            ],
                        ]) ?>
                        <div class="form-text">Pre-season preview or expectations. Rich text supported.</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-recap" class="form-label">Season Recap</label>
                        <?= $this->Form->control('team_season_recap', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-recap',
                            'rows' => 8,
                            'templates' => [
                                'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                            ],
                        ]) ?>
                        <div class="form-text">Post-season recap or summary. Rich text supported.</div>
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
                $tagFilter = 'teamseason-' . $teamSeason->id;
                $uploadContext = ['type' => 'teamseason', 'id' => $teamSeason->id];
                $aspectRatio = 16 / 9; // Widescreen aspect ratio (16:9, covers 4:3, 5:4 formats)
                echo $this->element('Admin/image_selector_modal', compact('modalId', 'targetFieldId', 'tagFilter', 'uploadContext', 'aspectRatio'));
                ?>

                <?php
                echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);
                echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
                echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
                echo $this->Html->script('/js/image-selector.js', ['block' => true]);

                $initialImageId = !empty($teamSeason->team_season_image) ? (string)(int)$teamSeason->team_season_image : '';
                $initialPreviewUrl = $initialImageId !== '' ? $this->ImageServe->url((int)$initialImageId, ['variant' => 'hero']) : '';
                $initialImageIdJson = json_encode($initialImageId) ?: '""';
                $initialPreviewUrlJson = json_encode($initialPreviewUrl) ?: '""';
                echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    const initialImageId = {$initialImageIdJson};
    const initialPreviewUrl = {$initialPreviewUrlJson};
    function initEditor(id){
        if (!document.getElementById(id) || typeof tinymce === 'undefined') return;
        tinymce.init({
            license_key: 'gpl',
            selector: '#' + id,
            menubar: false,
            plugins: 'image code lists advlist media preview quickbars save visualblocks visualchars',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | image media | code preview | save',
            quickbars_selection_toolbar: 'bold italic underline | quicklink blockquote | bullist numlist',
            image_title: true,
            automatic_uploads: true,
            images_upload_url: '/admin/images/upload',
            images_upload_credentials: true,
            convert_urls: false,
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '/admin/images/upload');
                    xhr.withCredentials = true;
                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable) {
                            progress(e.loaded / e.total * 100);
                        }
                    };
                    xhr.onload = function () {
                        if (xhr.status < 200 || xhr.status >= 300) { return reject('HTTP Error: ' + xhr.status); }
                        var raw = xhr.responseText;
                        var json;
                        try { json = JSON.parse(raw); } catch (err) {
                            console.error('TinyMCE upload invalid JSON response:', raw);
                            return reject('Invalid JSON');
                        }
                        if (!json.success || !json.image || !json.image.url) {
                            console.error('TinyMCE upload server response (error path):', json);
                            return reject(json.error || 'Upload failed');
                        }
                        resolve(json.image.url);
                    };
                    xhr.onerror = function () { reject('Image upload failed'); };
                    var formData = new FormData();
                    formData.append('upload', blobInfo.blob(), blobInfo.filename());
                    var csrf = document.querySelector('meta[name="csrfToken"]');
                    if (csrf) { xhr.setRequestHeader('X-CSRF-Token', csrf.getAttribute('content')); }
                    xhr.send(formData);
                });
            }
        });
    }
    initEditor('team-season-preview');
    initEditor('team-season-recap');

    // Team season image preview handler
    const imageField = document.getElementById('team-season-image-field');
    const imagePreview = document.getElementById('team-season-image-preview');
    const heroVariantButton = document.getElementById('team-season-hero-variant-btn');

    function updateHeroVariantButton() {
        if (!heroVariantButton || !imageField) {
            return;
        }

        const imageId = parseInt(imageField.value.trim(), 10);
        if (Number.isFinite(imageId) && imageId > 0) {
            heroVariantButton.href = '/admin/images/crop-hero/' + imageId;
            heroVariantButton.style.display = 'block';
        } else {
            heroVariantButton.style.display = 'none';
        }
    }

    function previewUrlForField() {
        if (!imageField) {
            return '';
        }

        const imageId = imageField.value.trim();
        const selectedUrl = imageField.dataset.selectedImageHeroUrl || imageField.dataset.selectedImageThumbnailUrl || imageField.dataset.selectedImageUrl || '';
        if (selectedUrl !== '') {
            return selectedUrl;
        }
        if (imageId === initialImageId) {
            return initialPreviewUrl;
        }

        return '';
    }

    function withCacheBust(url) {
        if (!url) {
            return '';
        }

        return url + (url.indexOf('?') === -1 ? '?' : '&') + '_ts=' + Date.now();
    }

    function updateImagePreview() {
        const imageId = imageField.value.trim();
        const previewUrl = previewUrlForField();
        if (imageId && !isNaN(parseInt(imageId, 10)) && previewUrl !== '') {
            const previewImg = imagePreview.querySelector('img');
            previewImg.src = withCacheBust(previewUrl);
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    }

    // Listen for changes to the image field (from modal or manual entry)
    if (imageField) {
        imageField.addEventListener('change', function () {
            updateImagePreview();
            updateHeroVariantButton();
        });

        // Show initial preview if image ID is set
        updateImagePreview();
        updateHeroVariantButton();
    }
});
JS, ['block' => true]);
                ?>
