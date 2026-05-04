<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $currentTags
 * @var mixed $games
 * @var mixed $opponents
 * @var mixed $sites
 * @var mixed $sports
 * @var mixed $tagString
 * @var mixed $teamSeasons
 * @var mixed $teams
 * @var \App\Model\Entity\Image $image
 */
$this->assign('title', 'Manage Image Tags');
$serveParams = ['w' => 400, 'h' => 400, 'fit' => 'cover'];
$previewUrl = $this->ImageServe->urlForImage($image, $serveParams);
$serveUrl = $this->ImageServe->urlForImage($image);
$storagePath = $image->storage_path ?? ($image->storage_subdir ? ($image->storage_subdir . '/' . $image->filename) : $image->filename);
$storagePath = ltrim((string)$storagePath, '/');
$directUrl = '/img/storage/' . $storagePath;
$displayName = $image->original_name ?: $image->filename;
$sizeLabel = $image->byte_size ? number_format($image->byte_size / 1024, 1) . ' KB' : null;
$dimensions = $image->width && $image->height ? ($image->width . '×' . $image->height) : null;
$uploadedAt = $image->created instanceof DateTimeInterface ? $image->created->format('M j, Y g:i A') : null;
$formUrl = ['action' => 'tags', $image->id];
?>
<div class="container py-4">
    <h1 class="mb-4">Manage Tags - Image #<?= h($image->id) ?></h1>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Image Preview</h5>
                </div>
                <div class="card-body text-center">
                    <div class="ratio ratio-4x3 mb-3">
                        <img
                            src="<?= h($previewUrl) ?>"
                            alt="<?= h($displayName) ?>"
                            class="img-fluid rounded"
                            loading="lazy"
                        />
                    </div>
                    <p class="small text-muted mb-2"><?= h($displayName) ?></p>
                    <dl class="row text-start small mb-0">
                        <?php if ($dimensions) : ?>
                            <dt class="col-5">Dimensions</dt>
                            <dd class="col-7 mb-2"><?= h($dimensions) ?></dd>
                        <?php endif; ?>
                        <?php if ($sizeLabel) : ?>
                            <dt class="col-5">File size</dt>
                            <dd class="col-7 mb-2"><?= h($sizeLabel) ?></dd>
                        <?php endif; ?>
                        <?php if ($uploadedAt) : ?>
                            <dt class="col-5">Uploaded</dt>
                            <dd class="col-7 mb-2"><?= h($uploadedAt) ?></dd>
                        <?php endif; ?>
                        <dt class="col-5">Direct file</dt>
                        <dd class="col-7 mb-2"><a href="<?= h($directUrl) ?>" target="_blank" rel="noopener">Storage path</a></dd>
                        <dt class="col-5">Serve URL</dt>
                        <dd class="col-7"><a href="<?= h($serveUrl) ?>" target="_blank" rel="noopener">Serve</a></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?= $this->Form->create(null, ['url' => $formUrl]) ?>

                    <?= $this->element('Admin/tag_selection', [
                        'teams' => $teams,
                        'teamSeasons' => $teamSeasons,
                        'games' => $games,
                        'sites' => $sites,
                        'opponents' => $opponents,
                        'sports' => $sports,
                        'currentTags' => $currentTags,
                        'tagString' => $tagString,
                        'showTeamId' => true,
                    ]) ?>

                    <div class="d-flex gap-2 mt-4">
                        <?= $this->Form->button('Update Tags', ['class' => 'btn btn-primary']) ?>
                        <?= $this->Html->link('Cancel', ['action' => 'edit', $image->id], ['class' => 'btn btn-secondary']) ?>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>
