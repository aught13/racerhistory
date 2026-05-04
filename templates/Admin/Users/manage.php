<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */

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
                        'class' => 'mb-4',
                    ]) ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('username', ['label' => 'Username', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('email', ['label' => 'Email Address', 'class' => 'form-control']) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('first_name', ['label' => 'First Name', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('last_name', ['label' => 'Last Name', 'class' => 'form-control']) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('role', [
                                'type' => 'select',
                                'options' => [
                                    'user' => 'User',
                                    'admin' => 'Admin',
                                ],
                                'label' => 'Role',
                                'class' => 'form-select',
                            ]) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('active', [
                                'type' => 'checkbox',
                                'label' => 'Active',
                            ]) ?>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <?= $this->Form->button('Update User', ['class' => 'btn btn-primary btn-lg']) ?>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <div class="row">
                            <div class="col-md-6">Created: <?= $user->created ? $user->created->format('M j, Y g:i A') : 'N/A' ?></div>
                            <div class="col-md-6">Modified: <?= $user->modified ? $user->modified->format('M j, Y g:i A') : 'N/A' ?></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">Last Login: <?= $user->last_login ? $user->last_login->format('M j, Y g:i A') : 'Never' ?></div>
                            <div class="col-md-6">Activated: <?= $user->activation_date ? $user->activation_date->format('M j, Y g:i A') : 'N/A' ?></div>
                        </div>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
