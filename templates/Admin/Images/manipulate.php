<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Image $image
 */
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2>Manipulate Image</h2>
                <?= $this->Html->link(
                    '← Back to Edit',
                    ['action' => 'edit', 'id' => $image->id],
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
                            id="previewImage"
                            src="<?= h($this->Url->build(['action' => 'serve', 'id' => $image->id])) ?>"
                            alt="<?= h($image->filename) ?>"
                            style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto;"
                        />
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small mb-1">
                            <strong>File:</strong> <?= h($image->filename) ?><br>
                            <strong>Size:</strong> <?= h($image->filesize) ?> bytes<br>
                            <strong>Dimensions:</strong> <?= h($image->width) ?> × <?= h($image->height) ?> px
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Column -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Adjustments</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, ['type' => 'post']) ?>

                    <!-- Crop Section -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-crop"></i> Crop</h6>

                        <div class="mb-3">
                            <label for="crop-x" class="form-label">X Position</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="crop-x-range" min="0" max="100" value="0">
                                <input type="number" class="form-control" id="crop-x" name="crop[x]" value="0" min="0">
                            </div>
                            <small class="form-text text-muted">pixels from left</small>
                        </div>

                        <div class="mb-3">
                            <label for="crop-y" class="form-label">Y Position</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="crop-y-range" min="0" max="100" value="0">
                                <input type="number" class="form-control" id="crop-y" name="crop[y]" value="0" min="0">
                            </div>
                            <small class="form-text text-muted">pixels from top</small>
                        </div>

                        <div class="mb-3">
                            <label for="crop-width" class="form-label">Width</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="crop-width-range" min="50" max="100" value="100">
                                <input type="number" class="form-control" id="crop-width" name="crop[width]" value="100" min="1">
                            </div>
                            <small class="form-text text-muted">pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="crop-height" class="form-label">Height</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="crop-height-range" min="50" max="100" value="100">
                                <input type="number" class="form-control" id="crop-height" name="crop[height]" value="100" min="1">
                            </div>
                            <small class="form-text text-muted">pixels</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Rotation Section -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-arrow-clockwise"></i> Rotation</h6>

                        <div class="btn-group w-100 mb-2" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(90)">90°</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(180)">180°</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(270)">270°</button>
                        </div>

                        <div>
                            <label for="rotate" class="form-label">Custom Angle</label>
                            <div class="input-group input-group-sm">
                                <input type="range" class="form-range" id="rotate-range" min="0" max="359" value="0">
                                <input type="number" class="form-control" id="rotate" name="rotate" value="0" min="0" max="359">
                                <span class="input-group-text">°</span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Brightness & Contrast -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-sun"></i> Brightness & Contrast</h6>

                        <div class="mb-3">
                            <label for="brightness" class="form-label">
                                Brightness <span class="badge bg-secondary" id="brightness-badge">0</span>
                            </label>
                            <input type="range" class="form-range" id="brightness-range" name="brightness" min="-100" max="100" value="0">
                            <small class="form-text text-muted">-100 (darker) to 100 (brighter)</small>
                        </div>

                        <div class="mb-3">
                            <label for="contrast" class="form-label">
                                Contrast <span class="badge bg-secondary" id="contrast-badge">0</span>
                            </label>
                            <input type="range" class="form-range" id="contrast-range" name="contrast" min="-100" max="100" value="0">
                            <small class="form-text text-muted">-100 (less) to 100 (more)</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Blur Section -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-circle"></i> Blur</h6>

                        <div>
                            <label for="blur" class="form-label">
                                Blur Amount <span class="badge bg-secondary" id="blur-badge">0</span>
                            </label>
                            <input type="range" class="form-range" id="blur-range" name="blur" min="0" max="100" value="0">
                            <small class="form-text text-muted">0 (none) to 100 (maximum)</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Buttons -->
                    <div class="d-flex gap-2">
                        <?= $this->Form->button(
                            '<i class="bi bi-check-circle"></i> Apply Changes',
                            ['type' => 'submit', 'class' => 'btn btn-primary flex-grow-1']
                        ) ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Reset</button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sync range and number inputs for crop
function syncInput(rangeId, numberId, callback) {
    const rangeEl = document.getElementById(rangeId);
    const numEl = document.getElementById(numberId);

    rangeEl?.addEventListener('input', function() {
        numEl.value = this.value;
        if (callback) callback();
    });

    numEl?.addEventListener('input', function() {
        rangeEl.value = this.value;
        if (callback) callback();
    });
}

// Sync brightness badge
function syncBadge(rangeId, badgeId) {
    const rangeEl = document.getElementById(rangeId);
    const badgeEl = document.getElementById(badgeId);

    rangeEl?.addEventListener('input', function() {
        badgeEl.textContent = this.value;
        badgeEl.className = 'badge ' + (this.value > 0 ? 'bg-success' : this.value < 0 ? 'bg-danger' : 'bg-secondary');
    });
}

// Set rotation from buttons
function setRotation(degrees) {
    document.getElementById('rotate-range').value = degrees;
    document.getElementById('rotate').value = degrees;
}

// Reset form
function resetForm() {
    document.querySelectorAll('input[type="range"]').forEach(el => el.value = 0);
    document.querySelectorAll('input[type="number"]').forEach(el => el.value = 0);
    document.getElementById('brightness-badge').textContent = '0';
    document.getElementById('contrast-badge').textContent = '0';
    document.getElementById('blur-badge').textContent = '0';
}

// Initialize sync on page load
document.addEventListener('DOMContentLoaded', function() {
    syncInput('crop-x-range', 'crop-x');
    syncInput('crop-y-range', 'crop-y');
    syncInput('crop-width-range', 'crop-width');
    syncInput('crop-height-range', 'crop-height');
    syncInput('rotate-range', 'rotate');

    syncBadge('brightness-range', 'brightness-badge');
    syncBadge('contrast-range', 'contrast-badge');
    syncBadge('blur-range', 'blur-badge');
});
</script>

<style>
.image-preview-container {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
