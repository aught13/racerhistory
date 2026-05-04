<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Image $image
 */
$this->assign('title', 'Crop Thumbnail');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2>Crop Thumbnail - Image #<?= h($image->id) ?></h2>
                <?= $this->Html->link(
                    '← Back to Edit',
                    ['action' => 'edit', $image->id],
                    ['class' => 'btn btn-outline-secondary'],
                ) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Select Crop Area</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Click and drag on the image to select the area to use for the thumbnail. The thumbnail will be scaled to 150×150px.
                    </p>
                    <div id="crop-container" style="max-width: 100%; max-height: 600px; overflow: hidden; position: relative; background: #f5f5f5; margin-bottom: 15px;">
                        <?php $serveUrl = $this->ImageServe->urlForImage($image); ?>
                        <img
                            id="crop-image"
                            src="<?= h($serveUrl) ?>"
                            alt="<?= h($image->original_name) ?>"
                            style="display: block; max-width: 100%; height: auto; cursor: crosshair;"
                        />
                        <div id="crop-overlay" style="position: absolute; border: 2px solid #007bff; background: rgba(0,123,255,0.1); cursor: move; display: none;">
                            <div class="resize-handle" style="position: absolute; right: -6px; bottom: -6px; width: 12px; height: 12px; background: #007bff; cursor: se-resize; border-radius: 2px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Preview</h5>
                </div>
                <div class="card-body text-center">
                    <canvas id="preview-canvas" width="150" height="150" style="border: 1px solid #ddd; max-width: 100%;"></canvas>
                    <p class="text-muted small mt-2">150×150 thumbnail preview</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Crop Info</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, ['type' => 'post']) ?>
                    <div class="mb-2">
                        <label class="form-label small">X Position (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_x" name="crop[x]" value="0" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Y Position (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_y" name="crop[y]" value="0" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Width (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_width" name="crop[width]" value="0" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Height (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_height" name="crop[height]" value="0" readonly>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <?= $this->Form->button(
                            '<i class="bi bi-check-circle"></i> Apply Crop',
                            [
                                'type' => 'submit',
                                'class' => 'btn btn-primary',
                                'escapeTitle' => false,
                            ],
                        ) ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetCrop()">Reset</button>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cropData = { x: 0, y: 0, width: 0, height: 0 };
let isDragging = false;
let isResizing = false;
let dragStart = { x: 0, y: 0 };
let imgNaturalWidth = 0;
let imgNaturalHeight = 0;
let imgDisplayWidth = 0;
let imgDisplayHeight = 0;

const img = document.getElementById('crop-image');
const container = document.getElementById('crop-container');
const overlay = document.getElementById('crop-overlay');
const resizeHandle = overlay?.querySelector('.resize-handle');
const previewCanvas = document.getElementById('preview-canvas');

function getScale() {
    return imgNaturalWidth / imgDisplayWidth;
}

function updatePreview() {
    const scale = getScale();
    const srcX = Math.round(cropData.x * scale);
    const srcY = Math.round(cropData.y * scale);
    const srcWidth = Math.round(cropData.width * scale);
    const srcHeight = Math.round(cropData.height * scale);

    if (srcWidth <= 0 || srcHeight <= 0 || !img.complete) return;

    const ctx = previewCanvas?.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, 150, 150);
    ctx.drawImage(
        img,
        srcX, srcY, srcWidth, srcHeight,
        0, 0, 150, 150
    );
}

function updateFormFields() {
    const scale = getScale();
    document.getElementById('crop_x').value = Math.round(cropData.x * scale);
    document.getElementById('crop_y').value = Math.round(cropData.y * scale);
    document.getElementById('crop_width').value = Math.round(cropData.width * scale);
    document.getElementById('crop_height').value = Math.round(cropData.height * scale);
}

function updateOverlay() {
    if (!overlay) return;
    overlay.style.left = cropData.x + 'px';
    overlay.style.top = cropData.y + 'px';
    overlay.style.width = cropData.width + 'px';
    overlay.style.height = cropData.height + 'px';
    overlay.style.display = cropData.width > 0 && cropData.height > 0 ? 'block' : 'none';
    updatePreview();
    updateFormFields();
}

function initCrop() {
    const rect = img.getBoundingClientRect();
    imgDisplayWidth = rect.width;
    imgDisplayHeight = rect.height;
    imgNaturalWidth = img.naturalWidth;
    imgNaturalHeight = img.naturalHeight;

    // Start with a maximized square that fits entirely in the displayed image
    const size = Math.min(imgDisplayWidth, imgDisplayHeight);
    cropData = {
        x: Math.floor((imgDisplayWidth - size) / 2),
        y: Math.floor((imgDisplayHeight - size) / 2),
        width: size,
        height: size,
    };
    updateOverlay();
}

function resetCrop() {
    initCrop();
}

// Mouse events for dragging and resizing
container?.addEventListener('mousedown', (e) => {
    if (!overlay) return;
    const containerRect = container.getBoundingClientRect();
    const mouseX = e.clientX - containerRect.left;
    const mouseY = e.clientY - containerRect.top;

    if (resizeHandle && e.target === resizeHandle) {
        isResizing = true;
        dragStart = { x: mouseX, y: mouseY };
    } else if (
        mouseX >= cropData.x &&
        mouseX <= cropData.x + cropData.width &&
        mouseY >= cropData.y &&
        mouseY <= cropData.y + cropData.height
    ) {
        isDragging = true;
        dragStart = { x: mouseX - cropData.x, y: mouseY - cropData.y };
    } else {
        // Start new crop selection
        isDragging = true;
        cropData.x = mouseX;
        cropData.y = mouseY;
        cropData.width = 1;
        cropData.height = 1;
        dragStart = { x: mouseX, y: mouseY };
    }
});

document.addEventListener('mousemove', (e) => {
    if (!isDragging && !isResizing) return;
    e.preventDefault();

    const containerRect = container.getBoundingClientRect();
    const mouseX = e.clientX - containerRect.left;
    const mouseY = e.clientY - containerRect.top;

    if (isDragging && !isResizing) {
        // Drawing new selection or moving existing
        if (cropData.width === 1 && cropData.height === 1) {
            // New selection - enforce square based on smallest delta
            const dx = mouseX - dragStart.x;
            const dy = mouseY - dragStart.y;
            const size = Math.max(20, Math.min(Math.abs(dx), Math.abs(dy)));

            cropData.x = dx < 0 ? dragStart.x - size : dragStart.x;
            cropData.y = dy < 0 ? dragStart.y - size : dragStart.y;

            // Clamp within image bounds
            cropData.x = Math.max(0, Math.min(cropData.x, imgDisplayWidth - size));
            cropData.y = Math.max(0, Math.min(cropData.y, imgDisplayHeight - size));
            cropData.width = size;
            cropData.height = size;
        } else {
            // Moving existing selection
            let newX = mouseX - dragStart.x;
            let newY = mouseY - dragStart.y;
            newX = Math.max(0, Math.min(newX, imgDisplayWidth - cropData.width));
            newY = Math.max(0, Math.min(newY, imgDisplayHeight - cropData.height));
            cropData.x = newX;
            cropData.y = newY;
        }
    } else if (isResizing) {
        // Resize by dragging bottom-right corner; keep square
        const deltaX = mouseX - cropData.x;
        const deltaY = mouseY - cropData.y;
        let newSize = Math.min(deltaX, deltaY);
        newSize = Math.max(20, newSize);
        newSize = Math.min(newSize, imgDisplayWidth - cropData.x, imgDisplayHeight - cropData.y);
        cropData.width = newSize;
        cropData.height = newSize;
    }

    updateOverlay();
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    isResizing = false;
});

img.addEventListener('load', () => {
    initCrop();
});

if (img.complete) {
    initCrop();
}
</script>

<style>
#crop-container {
    position: relative;
    display: inline-block;
    user-select: none;
}

#crop-overlay {
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
    transition: none;
}

.resize-handle {
    cursor: se-resize;
}
</style>
