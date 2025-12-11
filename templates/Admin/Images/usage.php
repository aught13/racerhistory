<?php
$this->assign('title', 'Image Usage');
?>
<div class="container py-4">
  <h1 class="mb-4">Image Usage - Image #<?= h($image->id) ?></h1>

  <div class="row g-4">
    <!-- Image Preview -->
    <div class="col-md-4">
      <?php $serveBase = '/images/serve/' . $image->id; ?>
      <figure>
        <img src="<?= h($serveBase) ?>" alt="Preview" class="img-fluid rounded border" />
        <figcaption class="mt-2 small text-muted">
          <strong><?= h($image->original_name) ?></strong><br>
          <?= h($image->width) ?>×<?= h($image->height) ?> • <?= h($image->byte_size) ?> bytes
        </figcaption>
      </figure>
    </div>

    <!-- Usage References -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-light">
          <h5 class="mb-0">
            <i class="bi bi-link-45deg"></i>
            Image References (<?= count($usages) ?>)
          </h5>
        </div>
        <div class="card-body">
          <?php if (empty($usages)): ?>
            <div class="alert alert-info" role="alert">
              <strong>No references found.</strong> This image is not currently referenced by any records.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Model</th>
                    <th>Record ID</th>
                    <th>Field</th>
                    <th>Context</th>
                    <th>Used On</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($usages as $usage): ?>
                    <tr>
                      <td>
                        <span class="badge bg-secondary"><?= h($usage->model) ?></span>
                      </td>
                      <td>
                        <code><?= h($usage->foreign_key) ?></code>
                      </td>
                      <td>
                        <?php if ($usage->field): ?>
                          <code><?= h($usage->field) ?></code>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($usage->context): ?>
                          <small class="text-muted"><?= h($usage->context) ?></small>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <small class="text-muted">
                          <?php if ($usage->created instanceof \DateTimeInterface): ?>
                            <?= h($usage->created->format('M j, Y')) ?>
                          <?php else: ?>
                            <?= h($usage->created) ?>
                          <?php endif; ?>
                        </small>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Related Actions -->
      <div class="card mt-4">
        <div class="card-header bg-light">
          <h6 class="mb-0">Actions</h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <?= $this->Html->link(
              'Edit Image',
              ['action' => 'edit', $image->id],
              ['class' => 'btn btn-outline-primary btn-sm']
            ) ?>
            <?= $this->Html->link(
              'Manage Tags',
              ['action' => 'tags', $image->id],
              ['class' => 'btn btn-outline-secondary btn-sm']
            ) ?>
            <?= $this->Html->link(
              'Back to Images',
              ['action' => 'index'],
              ['class' => 'btn btn-outline-secondary btn-sm']
            ) ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
