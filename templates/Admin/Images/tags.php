<?php
$this->assign('title', 'Manage Image Tags');
?>
<div class="container py-4">
  <h1 class="mb-4">Manage Tags - Image #<?= h($image->id) ?></h1>

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

    <!-- Tag Management Form -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-light">
          <h5 class="mb-0">Current Tags</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($currentTags)): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <?php foreach ($currentTags as $tag): ?>
                <span class="badge bg-info text-dark"><?= h($tag->name) ?> <code><?= h($tag->slug) ?></code></span>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mb-4" role="alert">
              <strong>No tags assigned.</strong> Add tags below to organize this image.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Edit Tags Form -->
      <div class="card mt-4">
        <div class="card-header bg-light">
          <h5 class="mb-0">Edit Tags</h5>
        </div>
        <div class="card-body">
          <?= $this->Form->create(null, ['url' => ['action' => 'tags', $image->id]]) ?>

          <div class="mb-3">
            <label for="tagsInput" class="form-label">Tags (comma-separated)</label>
            <textarea
              class="form-control"
              id="tagsInput"
              name="tags"
              rows="4"
              placeholder="Enter tags separated by commas&#10;Examples:&#10;  person-123&#10;  teamseason-456&#10;  roster"
            ><?= h($tagString) ?></textarea>
            <small class="form-text text-muted d-block mt-2">
              <strong>Common tag patterns:</strong>
              <ul class="mb-0 mt-1">
                <li><code>person-{id}</code> - Image of a specific person</li>
                <li><code>teamseason-{id}</code> - Image related to a team season</li>
                <li><code>roster</code> - Roster/lineup photo</li>
                <li><code>custom-tag</code> - Any custom label you want</li>
              </ul>
            </small>
          </div>

          <div class="d-flex gap-2">
            <?= $this->Form->button('Update Tags', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link('Cancel', ['action' => 'edit', $image->id], ['class' => 'btn btn-secondary']) ?>
          </div>

          <?= $this->Form->end() ?>
        </div>
      </div>

      <!-- Quick Tag Suggestions -->
      <div class="card mt-4">
        <div class="card-header bg-light">
          <h5 class="mb-0">Suggested Tags</h5>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3">
            Click a tag to add it to the field above:
          </p>
          <div class="btn-group-vertical w-100" role="group">
            <button type="button" class="btn btn-outline-secondary text-start" onclick="prependTag('person-')">
              <i class="bi bi-person"></i> Person Tag (person-id)
            </button>
            <button type="button" class="btn btn-outline-secondary text-start" onclick="prependTag('teamseason-')">
              <i class="bi bi-people"></i> Team Season Tag (teamseason-id)
            </button>
            <button type="button" class="btn btn-outline-secondary text-start" onclick="prependTag('roster')">
              <i class="bi bi-list-ul"></i> Roster
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function prependTag(tag) {
  const textarea = document.getElementById('tagsInput');
  const currentValue = textarea.value.trim();
  if (currentValue) {
    textarea.value = currentValue + ', ' + tag;
  } else {
    textarea.value = tag;
  }
  textarea.focus();
}
</script>
