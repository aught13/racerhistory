<?php
/**
 * Change Password Template
 *
 * Authenticated self-service password change form.
 * Requires current password verification before accepting a new one.
 *
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Change Password');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Change Password</h2>

                    <?= $this->Form->create(null, ['class' => 'needs-validation', 'novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="current-password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="current-password" name="current_password"
                                class="form-control" required autocomplete="current-password">
                            <span class="input-group-text p-0">
                                <button type="button" class="btn border-0 bg-transparent px-2"
                                    id="toggle-current-password" tabindex="-1" style="box-shadow:none;">
                                    <span class="bi bi-eye"></span>
                                </button>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new-password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" id="new-password" name="password"
                                class="form-control" required minlength="8" autocomplete="new-password">
                            <span class="input-group-text p-0">
                                <button type="button" class="btn border-0 bg-transparent px-2"
                                    id="toggle-new-password" tabindex="-1" style="box-shadow:none;">
                                    <span class="bi bi-eye"></span>
                                </button>
                            </span>
                        </div>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm-password" name="confirm_password"
                                class="form-control" required minlength="8" autocomplete="new-password">
                            <span class="input-group-text p-0">
                                <button type="button" class="btn border-0 bg-transparent px-2"
                                    id="toggle-confirm-password" tabindex="-1" style="box-shadow:none;">
                                    <span class="bi bi-eye"></span>
                                </button>
                            </span>
                        </div>
                    </div>

                    <?= $this->Form->button(__('Update Password'), ['class' => 'btn btn-primary w-100']) ?>
                    <?= $this->Form->secure(['current_password', 'password', 'confirm_password']) ?>
                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    function initToggles() {
        [
            ['toggle-current-password', 'current-password'],
            ['toggle-new-password', 'new-password'],
            ['toggle-confirm-password', 'confirm-password'],
        ].forEach(function (pair) {
            var btn = document.getElementById(pair[0]);
            var input = document.getElementById(pair[1]);
            if (!btn || !input) { return; }
            var clone = btn.cloneNode(true);
            btn.parentNode.replaceChild(clone, btn);
            clone.addEventListener('click', function () {
                input.type = input.type === 'password' ? 'text' : 'password';
                clone.innerHTML = input.type === 'password'
                    ? '<span class="bi bi-eye"></span>'
                    : '<span class="bi bi-eye-slash"></span>';
            });
        });
    }
    document.addEventListener('DOMContentLoaded', initToggles);
    document.addEventListener('turbo:load', initToggles);
}());
</script>
