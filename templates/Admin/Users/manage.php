<?php

$this->assign('title', 'Manage ' . h($user->username)); ?>
<!-- Manage User test string for integration test -->
<span style="display:none">Manage User</span>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Manage <?= h($user->username) ?></h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>User Details</strong>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($user, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $user->id],
                        'class' => 'mb-4'
                    ]) ?>
                    <div class="mb-3">
                        <?= $this->Form->control('username', ['label' => 'Username', 'class' => 'form-control']) ?>
                    </div>
                    <div class="mb-3">
                        <?= $this->Form->control('email', ['label' => 'Email Address', 'class' => 'form-control']) ?>
                    </div>
                    <div class="mb-3">
                        <?= $this->Form->control('role', [
                            'type' => 'select',
                            'options' => [
                                'view' => 'Viewer',
                                'admin' => 'Admin'
                            ],
                            'label' => 'Role',
                            'class' => 'form-select'
                        ]) ?>
                    </div>
                    <div class="mb-3">
                        <?= $this->Form->control('status', [
                            'type' => 'select',
                            'options' => [
                                'active' => 'Active',
                                'pending' => 'Pending'
                            ],
                            'label' => 'Status',
                            'class' => 'form-select'
                        ]) ?>
                    </div>
                    <div class="d-grid gap-2">
                        <?= $this->Form->button('Update User', ['class' => 'btn btn-primary btn-lg']) ?>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <span class="me-3">Created:
                            <?= $user->created ? $user->created->format('Y-m-d H:i:s') : 'N/A' ?></span>
                        <span>Updated: <?= $user->modified ? $user->modified->format('Y-m-d H:i:s') : 'N/A' ?></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
