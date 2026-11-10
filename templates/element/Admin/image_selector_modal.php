<?php
/**
 * Reusable Image Selector Modal
 *
 * Provides a modal for selecting existing images or uploading new ones with cropping.
 *
 * Variables:
 * - $modalId: Unique modal ID (required)
 * - $targetFieldId: ID of the field to populate with selected image ID (required)
 * - $tagFilter: Optional tag slug to filter images (e.g., 'person-123')
 * - $uploadContext: Optional context for tagging uploads (e.g., ['type' => 'person', 'id' => 123])
 *
 * @var \App\View\AppView $this
 */
$modalId = $modalId ?? 'image-selector-modal';
$targetFieldId = $targetFieldId ?? 'image-field';
$tagFilter = $tagFilter ?? null;
$uploadContext = $uploadContext ?? null;
$aspectRatio = $aspectRatio ?? 1;
$uploadContextValue = json_encode(
    $uploadContext,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
);
$tagSelectionOptions = array_merge([
    'teams' => [],
    'teamSeasons' => [],
    'games' => [],
    'sites' => [],
    'opponents' => [],
    'sports' => [],
], (array)($tagSelectionOptions ?? []));
$tagSelectorIdPrefix = $modalId . '_tag';
$tagFormId = $modalId . '-tag-form';
$skipCropId = $modalId . '-skip-crop';
?>

<div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-labelledby="<?= h($modalId) ?>Label" aria-hidden="true" data-controller="image-selector"
    data-image-selector-target-field-id-value="<?= h($targetFieldId) ?>"
    data-image-selector-tag-filter-value="<?= h((string)($tagFilter ?? '')) ?>"
    data-image-selector-upload-context-value="<?= h((string)$uploadContextValue) ?>"
    data-image-selector-aspect-ratio-value="<?= h(is_numeric($aspectRatio) ? (string)$aspectRatio : '') ?>"
    data-image-selector-csrf-token-value="<?= h((string)$this->request->getAttribute('csrfToken')) ?>">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="<?= h($modalId) ?>-select-tab" data-image-selector-target="selectTab" data-bs-toggle="tab" data-bs-target="#<?= h($modalId) ?>-select-pane" type="button" role="tab">
                            Select Existing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="<?= h($modalId) ?>-upload-tab" data-image-selector-target="uploadTab" data-bs-toggle="tab" data-bs-target="#<?= h($modalId) ?>-upload-pane" type="button" role="tab">
                            Upload New
                        </button>
                    </li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-image-selector-target="dismissButton"></button>
            </div>
            <div class="modal-body">
                <div class="tab-content">
                    <!-- Select Existing Tab -->
                    <div class="tab-pane fade show active" id="<?= h($modalId) ?>-select-pane" role="tabpanel">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="<?= h($modalId) ?>-search" placeholder="Search images..." data-image-selector-target="searchInput">
                        </div>
                        <div id="<?= h($modalId) ?>-gallery" class="row g-3 overflow-y-auto image-selector-gallery" data-image-selector-target="gallery">
                            <div class="col-12 text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload New Tab -->
                    <div class="tab-pane fade" id="<?= h($modalId) ?>-upload-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="<?= h($modalId) ?>-file-input" class="form-label">Select Image</label>
                                    <input type="file" class="form-control" id="<?= h($modalId) ?>-file-input" accept="image/*" data-image-selector-target="fileInput">
                                </div>
                                <div id="<?= h($modalId) ?>-crop-container" class="d-none" data-image-selector-target="cropContainer">
                                    <img id="<?= h($modalId) ?>-crop-image" class="mw-100" data-image-selector-target="cropImage">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Preview</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div id="<?= h($modalId) ?>-crop-preview" class="image-selector-crop-preview mx-auto border overflow-hidden d-none" data-image-selector-target="cropPreview"></div>
                                        <p id="<?= h($modalId) ?>-no-preview" class="text-muted" data-image-selector-target="noPreview">No image selected</p>
                                    </div>
                                </div>
                                <div class="mt-3 d-none" id="<?= h($modalId) ?>-crop-controls" data-image-selector-target="cropControls">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-rotate-left" data-image-selector-target="rotateLeftButton">
                                        <i class="bi bi-arrow-counterclockwise"></i> Rotate Left
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-rotate-right" data-image-selector-target="rotateRightButton">
                                        <i class="bi bi-arrow-clockwise"></i> Rotate Right
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-reset-crop" data-image-selector-target="resetCropButton">
                                        <i class="bi bi-arrow-repeat"></i> Reset
                                    </button>
                                </div>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="<?= h($skipCropId) ?>" data-image-selector-target="skipCropToggle">
                                    <label class="form-check-label" for="<?= h($skipCropId) ?>">Upload original image without cropping</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Tags for this upload</h5>
                                </div>
                                <div class="card-body">
                                    <form id="<?= h($tagFormId) ?>" class="m-0" data-image-selector-target="tagForm">
                                        <?= $this->element('Admin/tag_selection', [
                                            'teams' => $tagSelectionOptions['teams'],
                                            'teamSeasons' => $tagSelectionOptions['teamSeasons'],
                                            'games' => $tagSelectionOptions['games'],
                                            'sites' => $tagSelectionOptions['sites'],
                                            'opponents' => $tagSelectionOptions['opponents'],
                                            'sports' => $tagSelectionOptions['sports'],
                                            'currentTags' => [],
                                            'tagString' => '',
                                            'idPrefix' => $tagSelectorIdPrefix,
                                            'freeform' => [
                                                'label' => 'Additional tags (comma-separated slugs)',
                                                'help' => 'Use entity slugs such as person-123 or teamseason-456 to tag this upload.',
                                                'attributes' => [
                                                    'rows' => 2,
                                                    'id' => 'upload_tags',
                                                ],
                                            ],
                                        ]) ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-image-selector-target="dismissButton">Cancel</button>
                <button type="button" class="btn btn-primary d-none" id="<?= h($modalId) ?>-select-btn" data-image-selector-target="selectButton">Select Image</button>
                <button type="button" class="btn btn-primary d-none" id="<?= h($modalId) ?>-upload-btn" data-image-selector-target="uploadButton">Upload & Crop</button>
            </div>
        </div>
    </div>
</div>
