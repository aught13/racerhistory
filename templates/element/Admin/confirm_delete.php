<?php

/**
 * Concise reusable confirm delete modal.
 * Keep logic minimal; heavy JS previously bloated file size.
 * Expected data-* attributes on trigger buttons:
 *  - data-delete-url (required)
 *  - data-item-type (label)
 *  - data-associated (JSON array for listing)
 *  - data-ids (JSON array for bulk) + data-ids-name
 *  - data-form-id (optional existing form to submit)
 *  - data-bulk-action (optional)
 *
 * @var \App\View\AppView $this
 */

$modalId = $modalId ?? 'confirm-delete-modal';
?>
<div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true"
    data-controller="admin-confirm-delete"
    data-action="show.bs.modal->admin-confirm-delete#onShow">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-warning"><strong>Warning:</strong> This action cannot be undone.</p>
                <ul class="list-unstyled small mb-0" id="<?= h($modalId) ?>-assoc"
                    data-admin-confirm-delete-target="associated"></ul>
                <?= $this->Form->create(null, ['id' => $modalId . '-hidden-form', 'style' => 'display:none', 'secure' => false, 'data-admin-confirm-delete-target' => 'hiddenForm']) ?>
                <?= $this->Form->end() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="<?= h($modalId) ?>-delete-btn"
                    data-action="admin-confirm-delete#confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
<!-- Confirm-delete behavior is handled by the admin-confirm-delete Stimulus controller -->
