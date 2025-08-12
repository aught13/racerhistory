<?php $this->assign('title', 'Edit Sport'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Edit Sport</h2>
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
                            'label' => ['text' => 'Sport Name', 'class' => 'form-label'],
                            'required' => true,
                            'maxlength' => 162
                        ]) ?>
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
                        <?= $this->Form->postLink('Delete Sport',
                            ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id],
                            ['class' => 'btn btn-danger', 'confirm' => 'Are you sure you want to delete this sport?']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
