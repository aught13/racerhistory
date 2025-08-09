<?php // templates/Admin/Users/add.php
?>
<div class="container mt-4">
    <h2>Add New User</h2>
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend>User Details</legend>
        <?= $this->Form->control('username') ?>
        <?= $this->Form->control('email') ?>
        <div class="input-group mb-3">
            <?= $this->Form->control('password', ['type' => 'password', 'id' => 'admin-add-password']) ?>
            <button type="button" class="btn btn-outline-secondary" id="toggle-admin-add-password" tabindex="-1">
                <span class="bi bi-eye"></span>
            </button>
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
        <?= $this->Form->control('role', [
            'type' => 'select',
            'options' => [
                'view' => 'Viewer',
                'superadmin' => 'Admin'
            ],
            'default' => 'view'
        ]) ?>
        <?= $this->Form->control('status', [
            'type' => 'select',
            'options' => [
                'active' => 'Active',
                'pending' => 'Pending'
            ],
            'default' => 'active'
        ]) ?>
    </fieldset>
    <?= $this->Form->button('Add User', ['class' => 'btn btn-primary']) ?>
    <?= $this->Form->end() ?>
</div>
