<?php

/**
 * Admin Login Template
 * @var \App\View\AppView $this
 */
?>
<div class="row justify-content-center mt-5" data-controller="password-toggle">
    <div class="col-md-6 col-lg-4">
        <h2 class="mb-4 text-center">Admin Login</h2>
        <?= $this->Form->create(null, [
            'url' => ['controller' => 'Users', 'action' => 'login', 'prefix' => 'Admin'],
        ]) ?>
        <?= $this->Form->control('username', ['label' => 'Username', 'required' => true, 'class' => 'form-control mb-3']) ?>
        <div class="mb-3">
            <label for="admin-password" class="form-label">Password</label>
            <div class="input-group">
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'id' => 'admin-password',
                    'class' => 'form-control',
                    'label' => false,
                    'required' => true,
                    'data-password-toggle-target' => 'input',
                ]) ?>
                <span class="input-group-text p-0">
                    <button type="button" class="btn border-0 bg-transparent px-2" id="toggle-admin-password"
                        tabindex="-1" style="box-shadow:none;" data-password-toggle-target="button" data-action="password-toggle#toggle">
                        <span class="bi bi-eye"></span>
                    </button>
                </span>
            </div>
        </div>
        <?= $this->Form->button('Login', ['class' => 'btn btn-primary w-100']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
