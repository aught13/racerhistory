<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */

// templates/Admin/Users/add.php
?>
<div class="container mt-4">
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
                ]) ?>
                <button type="button" class="btn btn-outline-secondary" id="toggle-admin-add-password" tabindex="-1">
                    <span class="bi bi-eye"></span>
                </button>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('toggle-admin-add-password');
            var input = document.getElementById('admin-add-password');
            btn.addEventListener('click', function() {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.innerHTML = input.type === 'password' ? '<span class="bi bi-eye"></span>' : '<span class="bi bi-eye-slash"></span>';
            });
        });
        </script>
        <div class="row">
            <div class="col-md-6">
                <?= $this->Form->control('role', [
                    'type' => 'select',
                    'options' => [
                        'user' => 'User',
                        'admin' => 'Admin',
                    ],
                    'default' => 'user',
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
