<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */

// templates/Admin/Users/add.php
?>
<div class="container mt-4" data-controller="password-toggle">
    <h2>Add New User</h2>
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend>User Details</legend>
        <div class="row">
            <div class="col-md-6">
                <?= $this->Form->control('username', ['class' => 'form-control']) ?>
            </div>
            <div class="col-md-6">
                <?= $this->Form->control('email', ['class' => 'form-control']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $this->Form->control('first_name', ['label' => 'First Name', 'class' => 'form-control']) ?>
            </div>
            <div class="col-md-6">
                <?= $this->Form->control('last_name', ['label' => 'Last Name', 'class' => 'form-control']) ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="admin-add-password" class="form-label">Password</label>
            <div class="input-group">
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'id' => 'admin-add-password',
                    'class' => 'form-control',
                    'label' => false,
                    'data-password-toggle-target' => 'input',
                ]) ?>
                <button type="button" class="btn btn-outline-secondary" id="toggle-admin-add-password" tabindex="-1" data-password-toggle-target="button" data-action="password-toggle#toggle">
                    <span class="bi bi-eye"></span>
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $this->Form->control('role_id', [
                    'type' => 'select',
                    'options' => $roleOptions ?? [],
                    'empty' => 'Choose role',
                    'label' => 'RBAC Role',
                    'class' => 'form-select',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $this->Form->control('active', [
                    'type' => 'checkbox',
                    'label' => 'Active',
                    'checked' => true,
                ]) ?>
            </div>
        </div>
    </fieldset>
    <?= $this->Form->button('Add User', ['class' => 'btn btn-primary mt-3']) ?>
    <?= $this->Html->link('Cancel', ['action' => 'index'], ['class' => 'btn btn-secondary mt-3']) ?>
    <?= $this->Form->end() ?>
</div>
