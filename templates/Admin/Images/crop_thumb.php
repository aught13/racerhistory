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
