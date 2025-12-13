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
                        />
                        <canvas
                            id="previewCanvas"
                            aria-label="Live preview of image manipulations"
                            style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto;"
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
                            Live preview updates as you move sliders. Crop values are pixels in the original image.
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
                            [
                                'type' => 'submit',
                                'name' => 'mode',
                                'value' => 'apply',
                                'class' => 'btn btn-primary flex-grow-1',
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
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Reset</button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const MAX_PREVIEW_SOURCE_DIM = 1400;
let previewRaf = null;

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
    schedulePreview();
}

// Reset form
function resetForm() {
    document.querySelectorAll('input[type="range"]').forEach(el => el.value = 0);
    document.querySelectorAll('input[type="number"]').forEach(el => el.value = 0);
    document.getElementById('brightness-badge').textContent = '0';
    document.getElementById('contrast-badge').textContent = '0';
    document.getElementById('blur-badge').textContent = '0';
    initCropDefaults();
    schedulePreview();
}

function clamp(n, min, max) {
    return Math.min(max, Math.max(min, n));
}

function asInt(id) {
    const el = document.getElementById(id);
    const v = el ? parseInt(el.value, 10) : 0;
    return Number.isFinite(v) ? v : 0;
}

function getSourceDims() {
    const img = document.getElementById('sourceImage');
    const w = img?.naturalWidth ?? 0;
    const h = img?.naturalHeight ?? 0;
    return { w, h };
}

function updateCropConstraints() {
    const { w, h } = getSourceDims();
    if (!w || !h) return;

    const xEl = document.getElementById('crop-x');
    const yEl = document.getElementById('crop-y');
    const wEl = document.getElementById('crop-width');
    const hEl = document.getElementById('crop-height');
    const xRange = document.getElementById('crop-x-range');
    const yRange = document.getElementById('crop-y-range');
    const wRange = document.getElementById('crop-width-range');
    const hRange = document.getElementById('crop-height-range');

    const x = clamp(asInt('crop-x'), 0, Math.max(0, w - 1));
    const y = clamp(asInt('crop-y'), 0, Math.max(0, h - 1));
    const cwMax = Math.max(1, w - x);
    const chMax = Math.max(1, h - y);
    const cw = clamp(asInt('crop-width'), 1, cwMax);
    const ch = clamp(asInt('crop-height'), 1, chMax);

    if (xEl) xEl.value = String(x);
    if (yEl) yEl.value = String(y);
    if (wEl) wEl.value = String(cw);
    if (hEl) hEl.value = String(ch);
    if (xRange) xRange.value = String(x);
    if (yRange) yRange.value = String(y);
    if (wRange) wRange.value = String(cw);
    if (hRange) hRange.value = String(ch);

    if (xEl) xEl.max = String(Math.max(0, w - 1));
    if (yEl) yEl.max = String(Math.max(0, h - 1));
    if (wEl) wEl.max = String(cwMax);
    if (hEl) hEl.max = String(chMax);
    if (xRange) xRange.max = String(Math.max(0, w - 1));
    if (yRange) yRange.max = String(Math.max(0, h - 1));
    if (wRange) wRange.max = String(cwMax);
    if (hRange) hRange.max = String(chMax);
}

function initCropDefaults() {
    const { w, h } = getSourceDims();
    if (!w || !h) return;

    // Set sane defaults: full image crop.
    const setVal = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = String(value);
    };

    setVal('crop-x', 0);
    setVal('crop-y', 0);
    setVal('crop-width', w);
    setVal('crop-height', h);
    setVal('crop-x-range', 0);
    setVal('crop-y-range', 0);
    setVal('crop-width-range', w);
    setVal('crop-height-range', h);

    // Ensure mins are usable.
    const xRange = document.getElementById('crop-x-range');
    const yRange = document.getElementById('crop-y-range');
    const wRange = document.getElementById('crop-width-range');
    const hRange = document.getElementById('crop-height-range');
    if (xRange) xRange.min = '0';
    if (yRange) yRange.min = '0';
    if (wRange) wRange.min = '1';
    if (hRange) hRange.min = '1';
    const xEl = document.getElementById('crop-x');
    const yEl = document.getElementById('crop-y');
    const wEl = document.getElementById('crop-width');
    const hEl = document.getElementById('crop-height');
    if (xEl) xEl.min = '0';
    if (yEl) yEl.min = '0';
    if (wEl) wEl.min = '1';
    if (hEl) hEl.min = '1';

    updateCropConstraints();
}

function schedulePreview() {
    if (previewRaf !== null) return;
    previewRaf = window.requestAnimationFrame(() => {
        previewRaf = null;
        renderPreview();
    });
}

function renderPreview() {
    const img = document.getElementById('sourceImage');
    const canvas = document.getElementById('previewCanvas');
    const container = document.querySelector('.image-preview-container');
    if (!img || !canvas || !container) return;
    if (!img.complete || !img.naturalWidth || !img.naturalHeight) return;

    updateCropConstraints();

    const srcW = img.naturalWidth;
    const srcH = img.naturalHeight;

    const cropX = clamp(asInt('crop-x'), 0, Math.max(0, srcW - 1));
    const cropY = clamp(asInt('crop-y'), 0, Math.max(0, srcH - 1));
    const cropW = clamp(asInt('crop-width'), 1, Math.max(1, srcW - cropX));
    const cropH = clamp(asInt('crop-height'), 1, Math.max(1, srcH - cropY));
    const rotate = clamp(asInt('rotate'), 0, 359);
    const brightness = clamp(asInt('brightness-range'), -100, 100);
    const contrast = clamp(asInt('contrast-range'), -100, 100);
    const blur = clamp(asInt('blur-range'), 0, 100);

    // Downscale source for performance.
    const maxDim = Math.max(srcW, srcH);
    const sourceScale = Math.min(1, MAX_PREVIEW_SOURCE_DIM / maxDim);
    const scaledW = Math.max(1, Math.round(srcW * sourceScale));
    const scaledH = Math.max(1, Math.round(srcH * sourceScale));

    const base = document.createElement('canvas');
    base.width = scaledW;
    base.height = scaledH;
    const bctx = base.getContext('2d');
    if (!bctx) return;
    bctx.imageSmoothingEnabled = true;
    bctx.imageSmoothingQuality = 'high';
    bctx.drawImage(img, 0, 0, scaledW, scaledH);

    // Crop first (matches server order).
    const sx = Math.round(cropX * sourceScale);
    const sy = Math.round(cropY * sourceScale);
    const sw = Math.max(1, Math.round(cropW * sourceScale));
    const sh = Math.max(1, Math.round(cropH * sourceScale));
    const cropC = document.createElement('canvas');
    cropC.width = sw;
    cropC.height = sh;
    const cctx = cropC.getContext('2d');
    if (!cctx) return;
    cctx.drawImage(base, sx, sy, sw, sh, 0, 0, sw, sh);

    // Rotate next.
    const angle = (-rotate * Math.PI) / 180;
    const absCos = Math.abs(Math.cos(angle));
    const absSin = Math.abs(Math.sin(angle));
    const rotW = Math.max(1, Math.round(sw * absCos + sh * absSin));
    const rotH = Math.max(1, Math.round(sw * absSin + sh * absCos));

    const rotC = document.createElement('canvas');
    rotC.width = rotW;
    rotC.height = rotH;
    const rctx = rotC.getContext('2d');
    if (!rctx) return;
    rctx.imageSmoothingEnabled = true;
    rctx.imageSmoothingQuality = 'high';

    // Filters after rotate (matches server order, but visual result is same either way).
    const brightPct = 100 + brightness;
    const contrastPct = 100 + contrast;
    const blurPx = Math.round((blur / 100) * 12);
    rctx.filter = `brightness(${brightPct}%) contrast(${contrastPct}%) blur(${blurPx}px)`;
    rctx.translate(rotW / 2, rotH / 2);
    rctx.rotate(angle);
    rctx.drawImage(cropC, -sw / 2, -sh / 2);
    rctx.setTransform(1, 0, 0, 1, 0, 0);
    rctx.filter = 'none';

    // Fit to visible area.
    const maxDisplayW = container.clientWidth;
    const maxDisplayH = 500;
    const fitScale = Math.min(maxDisplayW / rotW, maxDisplayH / rotH, 1);
    const displayW = Math.max(1, Math.floor(rotW * fitScale));
    const displayH = Math.max(1, Math.floor(rotH * fitScale));

    const dpr = window.devicePixelRatio || 1;
    canvas.width = Math.floor(displayW * dpr);
    canvas.height = Math.floor(displayH * dpr);
    canvas.style.width = `${displayW}px`;
    canvas.style.height = `${displayH}px`;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.clearRect(0, 0, displayW, displayH);
    ctx.drawImage(rotC, 0, 0, rotW, rotH, 0, 0, displayW, displayH);
}

// Initialize sync on page load
document.addEventListener('DOMContentLoaded', function() {
    syncInput('crop-x-range', 'crop-x', () => { updateCropConstraints(); schedulePreview(); });
    syncInput('crop-y-range', 'crop-y', () => { updateCropConstraints(); schedulePreview(); });
    syncInput('crop-width-range', 'crop-width', () => { updateCropConstraints(); schedulePreview(); });
    syncInput('crop-height-range', 'crop-height', () => { updateCropConstraints(); schedulePreview(); });
    syncInput('rotate-range', 'rotate', schedulePreview);

    syncBadge('brightness-range', 'brightness-badge');
    syncBadge('contrast-range', 'contrast-badge');
    syncBadge('blur-range', 'blur-badge');

    // Ensure preview updates for sliders that don't have paired number inputs.
    ['brightness-range', 'contrast-range', 'blur-range'].forEach((id) => {
        const el = document.getElementById(id);
        el?.addEventListener('input', schedulePreview);
    });

    const img = document.getElementById('sourceImage');
    img?.addEventListener('load', () => {
        initCropDefaults();
        schedulePreview();
    });

    // If the image is already cached and loaded.
    if (img && img.complete) {
        initCropDefaults();
        schedulePreview();
    }
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
