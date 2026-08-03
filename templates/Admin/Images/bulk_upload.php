<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $currentTags
 * @var mixed $gameLabels
 * @var mixed $opponents
 * @var mixed $siteLabels
 * @var mixed $sports
 * @var mixed $teamSeasonLabels
 * @var mixed $teams
 */
?>

<div class="container py-4">
    <h1 class="mb-3">Bulk Upload Images</h1>
    <p class="text-muted mb-4">Select multiple images, then apply shared tags to all of them.</p>

    <?= $this->Form->create(null, [
        'type' => 'file',
        'id' => 'bulkUploadForm',
        'data-controller' => 'admin-image-bulk-upload',
        'data-admin-image-bulk-upload-target' => 'form',
        'url' => ['action' => 'bulkUpload'],
    ]) ?>
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Select Files</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="uploads" class="form-label">Image files</label>
                <input type="file" class="form-control" id="uploads" name="uploads[]" accept="image/*" multiple aria-describedby="uploadsHelp"
                    data-admin-image-bulk-upload-target="uploadsInput"
                    data-action="change->admin-image-bulk-upload#fileSelectionChanged">
                <div id="uploadsHelp" class="form-text">You can pick multiple files; supported types: JPG, PNG, GIF, WebP.</div>
            </div>

            <div id="fileList" class="row g-3" data-admin-image-bulk-upload-target="fileList"></div>

            <?= $this->element('Admin/tag_modal_trigger', [
                'subject' => 'images',
                'subjectId' => 0,
                'currentTags' => [],
                'syncHiddenInputs' => true,
            ]) ?>

            <div class="d-flex gap-2 mt-3">
                <button id="uploadAll" class="btn btn-primary" type="button" disabled
                    data-admin-image-bulk-upload-target="uploadButton"
                    data-action="click->admin-image-bulk-upload#uploadAll">
                    <span class="label" data-admin-image-bulk-upload-target="buttonLabel">Upload Selected</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                        data-admin-image-bulk-upload-target="buttonSpinner"></span>
                </button>
                <a class="btn btn-outline-secondary" href="<?= $this->Url->build(['action' => 'index']) ?>">Back to Images</a>
            </div>

            <div id="uploadStatus" class="mt-3" data-admin-image-bulk-upload-target="uploadStatus"></div>
        </div>
    </div>

    <!-- Per-file tagging: removed global entity-tags card. Use per-file "Apply Tags" links shown after upload. -->
    <?= $this->Form->end() ?>
</div>

