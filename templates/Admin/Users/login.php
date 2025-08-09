<?php
/**
 * Admin Login Template
 * @var \App\View\AppView $this
 */
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <h2 class="mb-4 text-center">Admin Login</h2>
        <?= $this->Form->create(null, [
            'url' => ['controller' => 'Users', 'action' => 'login', 'prefix' => 'Admin']
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
                    'required' => true
                ]) ?>
                <span class="input-group-text p-0">
                    <button type="button" class="btn border-0 bg-transparent px-2" id="toggle-admin-password"
                        tabindex="-1" style="box-shadow:none;">
                        <span class="bi bi-eye"></span>
                    </button>
                </span>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('toggle-admin-password');
            var input = document.getElementById('admin-password');
            btn.addEventListener('click', function() {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.innerHTML = input.type === 'password' ? '<span class="bi bi-eye"></span>' :
                    '<span class="bi bi-eye-slash"></span>';
            });
        });
        </script>
        <?= $this->Form->button('Login', ['class' => 'btn btn-primary w-100']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
