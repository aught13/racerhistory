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

<div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-labelledby="<?= h($modalId) ?>Label" aria-hidden="true" data-controller="image-selector">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="<?= h($modalId) ?>-select-tab" data-bs-toggle="tab" data-bs-target="#<?= h($modalId) ?>-select-pane" type="button" role="tab">
                            Select Existing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="<?= h($modalId) ?>-upload-tab" data-bs-toggle="tab" data-bs-target="#<?= h($modalId) ?>-upload-pane" type="button" role="tab">
                            Upload New
                        </button>
                    </li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="tab-content">
                    <!-- Select Existing Tab -->
                    <div class="tab-pane fade show active" id="<?= h($modalId) ?>-select-pane" role="tabpanel">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="<?= h($modalId) ?>-search" placeholder="Search images...">
                        </div>
                        <div id="<?= h($modalId) ?>-gallery" class="row g-3" style="max-height: 500px; overflow-y: auto;">
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
                                    <input type="file" class="form-control" id="<?= h($modalId) ?>-file-input" accept="image/*">
                                </div>
                                <div id="<?= h($modalId) ?>-crop-container" style="display: none;">
                                    <img id="<?= h($modalId) ?>-crop-image" style="max-width: 100%;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Preview</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div id="<?= h($modalId) ?>-crop-preview" style="width: 200px; height: 200px; overflow: hidden; margin: 0 auto; border: 1px solid #ddd; display: none;"></div>
                                        <p id="<?= h($modalId) ?>-no-preview" class="text-muted">No image selected</p>
                                    </div>
                                </div>
                                <div class="mt-3" id="<?= h($modalId) ?>-crop-controls" style="display: none;">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-rotate-left">
                                        <i class="bi bi-arrow-counterclockwise"></i> Rotate Left
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-rotate-right">
                                        <i class="bi bi-arrow-clockwise"></i> Rotate Right
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= h($modalId) ?>-reset-crop">
                                        <i class="bi bi-arrow-repeat"></i> Reset
                                    </button>
                                </div>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="<?= h($skipCropId) ?>">
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
                                    <form id="<?= h($tagFormId) ?>" class="m-0">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="<?= h($modalId) ?>-select-btn" style="display: none;">Select Image</button>
                <button type="button" class="btn btn-primary" id="<?= h($modalId) ?>-upload-btn" style="display: none;">Upload & Crop</button>
            </div>
        </div>
    </div>
</div>

<script>
// Store modal configuration
window.imageSelectorConfig = window.imageSelectorConfig || {};
window.imageSelectorConfig['<?= h($modalId) ?>'] = {
    targetFieldId: '<?= h($targetFieldId) ?>',
    tagFilter: <?= $tagFilter ? json_encode($tagFilter) : 'null' ?>,
    uploadContext: <?= $uploadContext ? json_encode($uploadContext) : 'null' ?>,
    aspectRatio: <?= isset($aspectRatio) ? (is_numeric($aspectRatio) ? $aspectRatio : 'null') : '1' ?>
};
</script>
