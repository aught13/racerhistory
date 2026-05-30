<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="container py-4" data-controller="image-upload">
    <h1 class="mb-4">Upload Image</h1>

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Select & Upload Image</h5>
                </div>
                <div class="card-body">
                    <form
                        id="uploadForm"
                        enctype="multipart/form-data"
                        action="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'upload']) ?>"
                        data-image-upload-target="form"
                    >
                        <div class="mb-3">
                            <label for="imageFile" class="form-label">Image File</label>
                            <input type="file" class="form-control" id="imageFile" name="file" accept="image/*" required data-image-upload-target="fileInput">
                            <small class="form-text text-muted">Supported: JPG, PNG, GIF, WebP (max 50MB)</small>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (optional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="person-123, teamseason-456, roster" data-image-upload-target="tags">
                            <small class="form-text text-muted">Comma-separated tag names or slugs</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <span id="uploadBtn" data-image-upload-target="submitLabel">Upload Image</span>
                            <span id="uploadSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" data-image-upload-target="submitSpinner"></span>
                        </button>
                    </form>

                    <div id="uploadStatus" class="mt-3 d-none" data-image-upload-target="status"></div>
                </div>
            </div>
        </div>

        <!-- Preview & Basic Manipulations -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Basic Upload Adjustments (Optional)</h5>
                </div>
                <div class="card-body">
                    <div id="previewContainer" class="mb-3 d-none" data-image-upload-target="previewContainer">
                        <img id="previewImage" src="" alt="Preview" class="img-fluid rounded" style="max-height: 300px; display: block; margin: 0 auto;" data-image-upload-target="previewImage">
                        <small class="form-text text-muted d-block mt-2">Selected image preview</small>
                    </div>

                    <div id="manipulationControls" class="d-none" data-image-upload-target="manipulationControls">
                        <!-- Rotate Quick Buttons -->
                        <div class="mb-3">
                            <label class="form-label">Rotation</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="click->image-upload#setRotation" data-image-upload-degrees-param="0">None</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="click->image-upload#setRotation" data-image-upload-degrees-param="90">90°</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="click->image-upload#setRotation" data-image-upload-degrees-param="180">180°</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="click->image-upload#setRotation" data-image-upload-degrees-param="270">270°</button>
                            </div>
                            <input type="hidden" id="rotate" name="rotate" value="0" data-image-upload-target="rotate">
                        </div>

                        <!-- Brightness -->
                        <div class="mb-3">
                            <label for="brightness" class="form-label">
                                Brightness <span class="badge bg-secondary" id="brightnessBadge" data-image-upload-target="brightnessBadge">0</span>
                            </label>
                            <input type="range" class="form-range" id="brightness" name="brightness" min="-100" max="100" value="0" data-image-upload-target="brightness">
                            <small class="form-text text-muted">-100 (darker) to 100 (brighter)</small>
                        </div>

                        <!-- Contrast -->
                        <div class="mb-3">
                            <label for="contrast" class="form-label">
                                Contrast <span class="badge bg-secondary" id="contrastBadge" data-image-upload-target="contrastBadge">0</span>
                            </label>
                            <input type="range" class="form-range" id="contrast" name="contrast" min="-100" max="100" value="0" data-image-upload-target="contrast">
                            <small class="form-text text-muted">-100 (less) to 100 (more)</small>
                        </div>

                        <div class="alert alert-info small mt-3">
                            <strong>Note:</strong> These basic adjustments are applied during upload. For more advanced crop/filters, upload first, then use <strong>Manipulate Image</strong> from the edit view.
                        </div>
                    </div>

                    <p id="noFileText" class="text-muted small mb-0">Select an image above to see adjustment options</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <?= $this->Html->link(
                '← Back to Images',
                ['action' => 'index'],
                ['class' => 'btn btn-outline-secondary'],
            ) ?>
        </div>
    </div>
</div>
