<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Image $image
 */
?>

<div class="container-fluid mt-4">
    <?php $serveBase = $this->ImageServe->urlForImage($image); ?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2>Manipulate Image</h2>
                <?= $this->Html->link(
                    '← Back to Edit',
                    ['action' => 'edit', $image->id],
                    ['class' => 'btn btn-outline-secondary']
                ) ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Preview Column -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Image Preview</h5>
                </div>
                <div class="card-body">
                    <div class="image-preview-container" style="background: #f5f5f5; padding: 20px; border-radius: 4px; text-align: center; position: relative;">
                        <img
                            id="sourceImage"
                            src="<?= h($serveBase) ?>"
                            alt="<?= h($image->filename) ?>"
                            style="display:none;"
                            crossorigin="anonymous"
                        />
                        <canvas
                            id="previewCanvas"
                            aria-label="Image with crop selection"
                            style="max-width: 100%; display: block; margin: 0 auto; cursor: crosshair;"
                        ></canvas>
                        <noscript>
                            <img
                                src="<?= h($serveBase) ?>"
                                alt="<?= h($image->filename) ?>"
                                style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto;"
                            />
                        </noscript>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small mb-1">
                            <strong>File:</strong> <?= h($image->filename) ?><br>
                            <strong>Size:</strong> <?= h($image->byte_size ?? $image->filesize ?? '') ?> bytes<br>
                            <strong>Dimensions:</strong> <?= h($image->width) ?> × <?= h($image->height) ?> px
                        </p>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-info-circle"></i> Drag the crop box or handles to select area. Rotation straightens the cropped result.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Column -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Crop & Rotate</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, ['type' => 'post']) ?>

                    <!-- Crop Section -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-crop"></i> Crop Selection</h6>

                        <!-- Aspect Ratio Buttons -->
                        <div class="mb-3">
                            <label class="form-label form-label-sm mb-2">Aspect Ratio</label>
                            <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Aspect ratio presets">
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(null, this)" id="ratio-free">Free</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(1, this)">1:1</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(4/3, this)">4:3</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(3/2, this)">3:2</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(16/9, this)">16:9</button>
                            </div>
                            <div class="btn-group btn-group-sm w-100" role="group" aria-label="Portrait aspect ratio presets">
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(3/4, this)">3:4</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(2/3, this)">2:3</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAspectRatio(9/16, this)">9:16</button>
                            </div>
                        </div>

                        <!-- Crop Coordinates (read-only display) -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="crop-x" class="form-label form-label-sm">X Position</label>
                                <input type="number" class="form-control form-control-sm" id="crop-x" name="crop[x]" value="0" min="0" readonly>
                            </div>
                            <div class="col-6">
                                <label for="crop-y" class="form-label form-label-sm">Y Position</label>
                                <input type="number" class="form-control form-control-sm" id="crop-y" name="crop[y]" value="0" min="0" readonly>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="crop-width" class="form-label form-label-sm">Width</label>
                                <input type="number" class="form-control form-control-sm" id="crop-width" name="crop[width]" value="0" min="1" readonly>
                            </div>
                            <div class="col-6">
                                <label for="crop-height" class="form-label form-label-sm">Height</label>
                                <input type="number" class="form-control form-control-sm" id="crop-height" name="crop[height]" value="0" min="1" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Rotation Section -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-arrow-clockwise"></i> Rotation</h6>

                        <div class="btn-group btn-group-sm w-100 mb-3" role="group" aria-label="Quick rotation">
                            <button type="button" class="btn btn-outline-secondary" onclick="setRotation(0)">0°</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setRotation(90)">90°</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setRotation(180)">180°</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setRotation(270)">270°</button>
                        </div>

                        <div>
                            <label for="rotate" class="form-label form-label-sm">Fine Tune Angle</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="rotate-range" min="-45" max="45" value="0" step="0.1">
                                <input type="number" class="form-control" id="rotate" name="rotate" value="0" min="-45" max="45" step="0.1">
                                <span class="input-group-text">°</span>
                            </div>
                            <small class="form-text text-muted">Use fine rotation to straighten photos (-45° to +45°)</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <?= $this->Form->button(
                            '<i class="bi bi-check-circle"></i> Apply & Save',
                            [
                                'type' => 'submit',
                                'name' => 'mode',
                                'value' => 'apply',
                                'class' => 'btn btn-primary',
                                'escapeTitle' => false,
                            ]
                        ) ?>
                        <?= $this->Form->button(
                            '<i class="bi bi-files"></i> Save As Copy',
                            [
                                'type' => 'submit',
                                'name' => 'mode',
                                'value' => 'copy',
                                'class' => 'btn btn-outline-primary',
                                'escapeTitle' => false,
                            ]
                        ) ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetAll()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset All
                        </button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-question-circle"></i> Quick Tips</h6>
                    <ul class="small mb-0">
                        <li><strong>Drag</strong> the crop box to move it</li>
                        <li><strong>Drag corners/edges</strong> to resize</li>
                        <li><strong>Lock aspect ratio</strong> for consistent crops</li>
                        <li><strong>Fine rotation</strong> straightens tilted photos</li>
                        <li><strong>Apply</strong> overwrites original, <strong>Copy</strong> creates new</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/crop-selector.js"></script>
<script>
    let cropSelector = null;
    let currentAspectRatio = null;

    // Set rotation from buttons
    function setRotation(degrees) {
        document.getElementById('rotate-range').value = degrees;
        document.getElementById('rotate').value = degrees;
        if (cropSelector) {
            cropSelector.setRotation(parseFloat(degrees) || 0);
        }
    }

    // Set aspect ratio
    function setAspectRatio(ratio, btn) {
        currentAspectRatio = ratio;
        if (cropSelector) {
            cropSelector.setAspectRatio(ratio);
        }

        // Update button states
        document.querySelectorAll('[onclick^="setAspectRatio"]').forEach(btn => {
            btn.classList.remove('active');
        });

        if (ratio === null) {
            document.getElementById('ratio-free').classList.add('active');
        } else {
            if (btn) btn.classList.add('active');
        }
    }

    // Reset all controls
    function resetAll() {
        document.getElementById('rotate-range').value = 0;
        document.getElementById('rotate').value = 0;

        // Reset aspect ratio to free
        setAspectRatio(null);

        // Reset crop to full image
        const img = document.getElementById('sourceImage');
        if (img && img.complete && img.naturalWidth && cropSelector) {
            cropSelector.setCropBox(0, 0, img.naturalWidth, img.naturalHeight);
        }
    }

    function syncRotation(fromId, toId) {
        const from = document.getElementById(fromId);
        const to = document.getElementById(toId);

        from?.addEventListener('input', function() {
            to.value = this.value;
        });
    }

    function updateCropInputs(crop) {
        document.getElementById('crop-x').value = crop.x;
        document.getElementById('crop-y').value = crop.y;
        document.getElementById('crop-width').value = crop.width;
        document.getElementById('crop-height').value = crop.height;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const img = document.getElementById('sourceImage');
        const canvasEl = document.getElementById('previewCanvas');

        if (img && canvasEl) {
            cropSelector = new CropSelector('previewCanvas', 'sourceImage', {
                onCropChange: (crop) => {
                    updateCropInputs(crop);
                }
            });
        }

        // Sync rotation slider and input and trigger preview redraw
        const rangeEl = document.getElementById('rotate-range');
        const inputEl = document.getElementById('rotate');
        if (rangeEl && inputEl) {
            rangeEl.addEventListener('input', function() {
                inputEl.value = this.value;
                if (cropSelector) cropSelector.setRotation(parseFloat(this.value) || 0);
            });
            inputEl.addEventListener('input', function() {
                rangeEl.value = this.value;
                if (cropSelector) cropSelector.setRotation(parseFloat(this.value) || 0);
            });
        }

        // Set initial aspect ratio button state
        document.getElementById('ratio-free').classList.add('active');
    });

    // Export functions to global scope for onclick handlers
    window.setRotation = setRotation;
    window.setAspectRatio = setAspectRatio;
    window.resetAll = resetAll;
</script>

<style>
.image-preview-container {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-group .btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
