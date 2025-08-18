<?php $this->assign('title', 'View Person'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Person Details</h2>
                    <div class="btn-group" role="group" aria-label="Actions">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>" class="btn btn-primary btn-sm">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'delete', $person->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>"
                            data-item-type="person">Delete</button>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Display Name</dt><dd class="col-sm-9"><?= h($person->display) ?></dd>
                        <dt class="col-sm-3">First</dt><dd class="col-sm-9"><?= h($person->first) ?></dd>
                        <dt class="col-sm-3">Last</dt><dd class="col-sm-9"><?= h($person->last) ?></dd>
                        <dt class="col-sm-3">Full</dt><dd class="col-sm-9"><?= h($person->full) ?></dd>
                        <dt class="col-sm-3">Birth</dt><dd class="col-sm-9"><?= h($person->birth) ?></dd>
                        <dt class="col-sm-3">Death</dt><dd class="col-sm-9"><?= h($person->death) ?></dd>
                        <dt class="col-sm-3">Image</dt><dd class="col-sm-9"><?= h($person->person_image) ?></dd>
                        <dt class="col-sm-3">Bio</dt><dd class="col-sm-9"><pre class="mb-0" style="white-space:pre-wrap;"><?= h($person->bio) ?></pre></dd>
                        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= h($person->created_at) ?></dd>
                        <dt class="col-sm-3">Updated</dt><dd class="col-sm-9"><?= h($person->updated_at) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>
