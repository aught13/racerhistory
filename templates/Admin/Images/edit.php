<?php
$this->assign('title', 'Edit Image');
?>
<div class="container py-4">
  <h1 class="mb-4">Edit Image #<?= h($image->id) ?></h1>
  <div class="row g-4">
    <div class="col-md-4">
      <?php $serveUrl = $this->ImageServe->urlForImage($image); ?>
      <figure>
        <img src="<?= h($serveUrl) ?>" alt="Preview" class="img-fluid rounded border" />
        <figcaption class="mt-2 small text-muted">Original (public)</figcaption>
      </figure>
      <?php $variants = is_string($image->variants) ? json_decode($image->variants, true) : (array)$image->variants; ?>
      <?php if ($variants): ?>
        <div class="row g-2 mt-3">
          <?php foreach ($variants as $name => $meta): ?>
            <?php
              $meta = (array)$meta;
              $vw = isset($meta['width']) && is_numeric($meta['width']) ? (int)$meta['width'] : null;
              $vh = isset($meta['height']) && is_numeric($meta['height']) ? (int)$meta['height'] : null;
              $vmime = isset($meta['mime']) ? (string)$meta['mime'] : '';

                // Always use the stored variant so custom crops (e.g., thumb) are shown.
                $thumbUrl = $this->ImageServe->urlForImage($image, ['variant' => (string)$name]);
            ?>
            <div class="col-4 text-center">
              <img src="<?= h($thumbUrl) ?>" alt="<?= h($name) ?>" class="img-fluid border rounded" />
              <div class="small mt-1"><?= h($name) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Image Info Card -->
      <div class="card mt-4">
        <div class="card-header bg-light">
          <h6 class="mb-0">Image Info</h6>
        </div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-sm-4">ID:</dt>
            <dd class="col-sm-8"><?= h($image->id) ?></dd>
            <dt class="col-sm-4">Size:</dt>
            <dd class="col-sm-8"><?= h($image->byte_size) ?> bytes</dd>
            <dt class="col-sm-4">Dimensions:</dt>
            <dd class="col-sm-8"><?= h($image->width) ?>×<?= h($image->height) ?></dd>
            <dt class="col-sm-4">Created:</dt>
            <dd class="col-sm-8"><?= h($image->created->format('M j, Y g:i A')) ?></dd>
            <dt class="col-sm-4">Modified:</dt>
            <dd class="col-sm-8"><?= h($image->modified->format('M j, Y g:i A')) ?></dd>
          </dl>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mt-3 d-grid gap-2">
        <?= $this->Html->link(
          'Crop Thumbnail',
          ['action' => 'cropThumb', $image->id],
          ['class' => 'btn btn-warning btn-sm']
        ) ?>
        <?= $this->Html->link(
          'Manipulate Image',
          ['action' => 'manipulate', $image->id],
          ['class' => 'btn btn-warning btn-sm']
        ) ?>
        <?= $this->Html->link(
          'View Tags',
          ['action' => 'tags', $image->id],
          ['class' => 'btn btn-info btn-sm']
        ) ?>
        <?= $this->Html->link(
          'View Usage',
          ['action' => 'usage', $image->id],
          ['class' => 'btn btn-secondary btn-sm']
        ) ?>
        <?= $this->Form->postButton(
          'Delete Image',
          ['action' => 'delete', $image->id],
          ['class' => 'btn btn-danger btn-sm', 'confirm' => 'Are you sure?']
        ) ?>
      </div>
    </div>
    <div class="col-md-8">
      <?= $this->Form->create($image) ?>
      <div class="mb-3">
        <?= $this->Form->control('original_name', ['label' => 'Original Name', 'class' => 'form-control']) ?>
        <small class="form-text text-muted">The original filename for reference</small>
      </div>
      <div class="mb-3">
        <?= $this->Form->control('status', ['label' => 'Status', 'class' => 'form-select', 'options' => ['active' => 'Active', 'archived' => 'Archived']]) ?>
        <small class="form-text text-muted">Archived images won't be served</small>
      </div>

      <!-- Tags (read-only here; editing happens on Manage Tags screen) -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h6 class="mb-0">Tags</h6>
        </div>
        <div class="card-body">
          <p class="mb-3 small text-muted">Tags are listed here for reference. Use the <strong>View Tags</strong> action to manage tags.</p>
          <?php if (!empty($currentTags)): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($currentTags as $tag): ?>
                <span class="badge bg-secondary"><?= h($tag->name) ?></span>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="small text-muted">No tags assigned.</div>
          <?php endif; ?>
          <div class="mt-2">
            <?= $this->Html->link('Manage Tags', ['action' => 'tags', $image->id], ['class' => 'btn btn-sm btn-info']) ?>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <?= $this->Form->button('Save Changes', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Back', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
      </div>
      <?= $this->Form->end() ?>

      <!-- Note: Tags are managed on the Manage Tags screen -->
    </div>
  </div>
</div>
