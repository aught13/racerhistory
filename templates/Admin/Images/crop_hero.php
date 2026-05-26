<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Image $image
 */
$this->assign('title', 'Crop Hero Variant');
$aspectRatio = 1400 / 720;
?>

<div class="container-fluid mt-4" data-controller="hero-crop" data-hero-crop-aspect-ratio-value="<?= h((string)$aspectRatio) ?>">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2>Crop Hero Variant - Image #<?= h($image->id) ?></h2>
                <?= $this->Html->link(
                    '← Back to Edit',
                    ['action' => 'edit', $image->id],
                    ['class' => 'btn btn-outline-secondary'],
                ) ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Select Hero Crop Area</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Select the area to use for the hero variant. The stored crop will be saved as a 1400×720 WebP derivative.
                    </p>
                    <div class="image-preview-container" style="background: #f5f5f5; padding: 20px; border-radius: 4px; text-align: center; position: relative;">
                        <?= $this->ImageServe->picture(
                            $image,
                            [],
                            [
                                'id' => 'sourceImage',
                                'data-hero-crop-target' => 'image',
                                'alt' => (string)$image->original_name,
                                'loading' => 'eager',
                                'decoding' => 'sync',
                                'fetchpriority' => 'high',
                                'style' => 'display:none;',
                            ],
                        ) ?>
                        <canvas
                            id="previewCanvas"
                            data-hero-crop-target="canvas"
                            aria-label="Hero crop selection"
                            style="max-width: 100%; display: block; margin: 0 auto; cursor: crosshair;"
                        ></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Crop Info</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, ['type' => 'post']) ?>
                    <div class="mb-2">
                        <label class="form-label small">X Position (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_x" name="crop[x]" value="0" readonly data-hero-crop-target="cropX">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Y Position (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_y" name="crop[y]" value="0" readonly data-hero-crop-target="cropY">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Width (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_width" name="crop[width]" value="0" readonly data-hero-crop-target="cropWidth">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Height (px)</label>
                        <input type="number" class="form-control form-control-sm" id="crop_height" name="crop[height]" value="0" readonly data-hero-crop-target="cropHeight">
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <?= $this->Form->button(
                            '<i class="bi bi-check-circle"></i> Save Hero Variant',
                            [
                                'type' => 'submit',
                                'class' => 'btn btn-primary',
                                'escapeTitle' => false,
                            ],
                        ) ?>
                        <button type="button" class="btn btn-outline-secondary" data-action="hero-crop#reset">Reset</button>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Notes</h6>
                    <ul class="small mb-0">
                        <li>The hero variant is only created when you save it here.</li>
                        <li>Season and blog hero views use this stored crop when present.</li>
                        <li>Other images keep using the original or thumb variants.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.image-preview-container {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
