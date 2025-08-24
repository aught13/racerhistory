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
                    <div class="row">
                        <div class="col-md-8">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Display Name</dt><dd class="col-sm-8"><?= h($person->display) ?></dd>
                                <dt class="col-sm-4">First</dt><dd class="col-sm-8"><?= h($person->first) ?></dd>
                                <dt class="col-sm-4">Last</dt><dd class="col-sm-8"><?= h($person->last) ?></dd>
                                <dt class="col-sm-4">Full</dt><dd class="col-sm-8"><?= h($person->full) ?></dd>
                                <dt class="col-sm-4">Birth</dt><dd class="col-sm-8"><?= h($person->birth) ?></dd>
                                <dt class="col-sm-4">Death</dt><dd class="col-sm-8"><?= h($person->death) ?></dd>
                                <dt class="col-sm-4">Image ID</dt><dd class="col-sm-8"><?= h($person->person_image) ?></dd>
                                <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?= h($person->created_at) ?></dd>
                                <dt class="col-sm-4">Updated</dt><dd class="col-sm-8"><?= h($person->updated_at) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <?= $this->element('person_image', ['person' => $person, 'size' => 'large']) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($person->bio)): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Biography</h5>
                            <div class="person-bio">
                                <?php
                                $bio = (string)($person->bio ?? '');
                                // Basic sanitization: strip script/style tags while allowing common formatting
                                $bioClean = preg_replace('#<\/(script|style)>#i', '', preg_replace('#<(script|style)[^>]*>.*?<\/\1>#is', '', $bio));
                                echo $bioClean;
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>
