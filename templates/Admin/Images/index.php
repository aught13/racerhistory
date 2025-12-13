<?php
$this->assign('title', 'Images');
?>
<div class="container py-4">
  <h1 class="mb-4">Images</h1>
  <table class="table table-sm align-middle table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Preview</th>
        <th>Original Name</th>
        <th>Mime</th>
        <th>Size</th>
        <th>Dimensions</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($images as $img): ?>
      <tr>
        <td><?= h($img->id) ?></td>
        <td>
          <?php
          $thumbUrl = $this->ImageServe->urlForImage($img, [
            'w' => 60,
            'h' => 60,
            'fit' => 'cover',
          ]);
          ?>
          <img src="<?= h($thumbUrl) ?>" alt="" class="img-thumbnail" style="max-width:60px; height:auto;" />
        </td>
        <td><?= h($img->original_name ?: $img->filename) ?></td>
        <td><code><?= h($img->mime) ?></code></td>
        <td><?= $this->Number->toReadableSize($img->byte_size) ?></td>
        <td><?= h($img->width . '×' . $img->height) ?></td>
        <td><span class="badge bg-secondary"><?= h($img->status) ?></span></td>
        <td>
          <?= $this->Html->link('Edit', ['action'=>'edit',$img->id], ['class'=>'btn btn-sm btn-primary']) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
