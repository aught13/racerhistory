<?php
$this->assign('title', 'Edit Image');
?>
<div class="container py-4">
  <h1 class="mb-4">Edit Image #<?= h($image->id) ?></h1>
  <div class="row g-4">
    <div class="col-md-4">
      <?php $serveBase = '/images/serve/' . $image->id; ?>
      <figure>
        <img src="<?= h($serveBase) ?>" alt="Preview" class="img-fluid rounded border" />
        <figcaption class="mt-2 small text-muted">Original (public)</figcaption>
      </figure>
      <?php $variants = is_string($image->variants) ? json_decode($image->variants,true) : (array)$image->variants; ?>
      <?php if ($variants): ?>
        <div class="row g-2">
      <?php foreach ($variants as $name => $meta): ?>
            <div class="col-4 text-center">
        <img src="<?= h($serveBase . '?variant=' . rawurlencode($name)) ?>" alt="<?= h($name) ?>" class="img-fluid border rounded" />
              <div class="small mt-1"><?= h($name) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-8">
      <?= $this->Form->create($image) ?>
      <div class="mb-3">
        <?= $this->Form->control('original_name', ['label'=>'Original Name','class'=>'form-control']) ?>
      </div>
      <div class="mb-3">
        <?= $this->Form->control('status', ['label'=>'Status','class'=>'form-select','options'=>['active'=>'Active','archived'=>'Archived']]) ?>
      </div>
      <div class="d-flex gap-2">
        <?= $this->Form->button('Save Changes', ['class'=>'btn btn-success']) ?>
        <?= $this->Html->link('Back', ['action'=>'index'], ['class'=>'btn btn-secondary']) ?>
      </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
