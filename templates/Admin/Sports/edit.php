<?php $this->assign('title', 'Edit Sport'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Edit Sport</h2>
                    <p class="text-muted mb-0 mt-2">
                        Update sport category information. Sport names must remain unique across the system.
                    </p>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($sport, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id],
                        'class' => 'needs-validation',
                        'novalidate' => true
                    ]) ?>

                    <div class="mb-3">
                        <?= $this->Form->control('sport_name', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => ['text' => 'Sport Name *', 'class' => 'form-label'],
                            'placeholder' => 'Enter sport name (e.g., Basketball, Football, Soccer)',
                            'required' => true,
                            'maxlength' => 162
                        ]) ?>
                        <div class="form-text">
                            Name of the sport category (maximum 162 characters). Must be unique across all sports.
                            Examples: Basketball, Football, Soccer, Tennis, etc.
                        </div>
                        <div class="invalid-feedback">
                            Please provide a valid sport name.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button('Update Sport', [
                            'type' => 'submit',
                            'class' => 'btn btn-success'
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'index']) ?>"
                           class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>

                    <div class="mt-3">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                            data-item-type="sport">
                            Delete Sport
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'sport']) ?>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
