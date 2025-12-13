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

              $thumbUrl = $this->ImageServe->urlForImage($image, ['variant' => (string)$name]);

              // Prefer on-the-fly derivatives when a size is known.
              if (($vw && $vw > 0) || ($vh && $vh > 0)) {
                  $params = [];
                  if ($vw && $vw > 0) {
                      $params['w'] = $vw;
                  }
                  if ($vh && $vh > 0) {
                      $params['h'] = $vh;
                  }
                  if (($vw && $vw > 0) && ($vh && $vh > 0)) {
                      $params['fit'] = 'cover';
                  }
                  if ($vmime === 'image/png') {
                      $params['fm'] = 'png';
                  } elseif ($vmime === 'image/webp') {
                      $params['fm'] = 'webp';
                  } elseif ($vmime === 'image/jpeg') {
                      $params['fm'] = 'jpg';
                  }

                $thumbUrl = $this->ImageServe->urlForImage($image, $params);
              }
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

      <!-- Tags Section -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h6 class="mb-0">Tags</h6>
        </div>
        <div class="card-body">
          <p class="mb-3 small text-muted">
            Manage tags for this image. Tags help organize and categorize images by type, person, team, etc.
          </p>
          <div class="mb-3">
            <label for="tagsInput" class="form-label">Image Tags</label>
            <input
              type="text"
              class="form-control"
              id="tagsInput"
              name="tags"
              placeholder="e.g., person-123, teamseason-456, roster"
              value="<?= isset($tagString) ? h($tagString) : '' ?>"
            />
            <small class="form-text text-muted">
              Enter comma-separated tags. Common tags: <code>person-{id}</code>, <code>teamseason-{id}</code>, <code>roster</code>
            </small>
          </div>
          <?php if (!empty($currentTags)): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($currentTags as $tag): ?>
                <span class="badge bg-secondary"><?= h($tag->name) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick="document.getElementById('tagsForm').submit()"
          >
            Update Tags
          </button>
        </div>
      </div>

      <div class="d-flex gap-2">
        <?= $this->Form->button('Save Changes', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Back', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
      </div>
      <?= $this->Form->end() ?>

      <!-- Separate Form for Tags Update (POST) -->
      <form id="tagsForm" method="post" action="<?= $this->Url->build(['action' => 'tags', $image->id]) ?>" style="display: none;">
        <input type="hidden" name="tags" id="tagsFormInput" value="" />
        <?= $this->Form->unlocked('tags') ?>
      </form>
    </div>
  </div>
</div>
