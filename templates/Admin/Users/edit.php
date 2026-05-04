<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<?php $this->assign('title', 'Edit User'); ?>
<div class="container py-4">
    <h1 class="mb-4">Edit User</h1>
    <?= $this->Form->create($user) ?>
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
    <div class="row">
        <div class="col-md-6">
            <?= $this->Form->control('role', [
                'type' => 'select',
                'options' => [
                    'user' => 'User',
                    'admin' => 'Admin',
                ],
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $this->Form->control('active', [
                'type' => 'checkbox',
                'label' => 'Active',
            ]) ?>
        </div>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
        <div class="input-group">
            <?= $this->Form->control('password', [
                'type' => 'password',
                'id' => 'admin-edit-password',
                'class' => 'form-control',
                'label' => false,
                'value' => '',
            ]) ?>
            <button type="button" class="btn btn-outline-secondary" id="toggle-admin-edit-password" tabindex="-1">
                <span class="bi bi-eye"></span>
            </button>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('toggle-admin-edit-password');
        var input = document.getElementById('admin-edit-password');
        if (btn && input) {
            btn.addEventListener('click', function() {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.innerHTML = input.type === 'password' ? '<span class="bi bi-eye"></span>' : '<span class="bi bi-eye-slash"></span>';
            });
        }
    });
    </script>
    <?= $this->Form->button('Update User', ['class' => 'btn btn-primary']) ?>
    <?= $this->Html->link('Cancel', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    <?= $this->Form->end() ?>
</div>
