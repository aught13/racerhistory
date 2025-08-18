<?php
/**
 * User Login Template
 *
 * Responsive user authentication form with Bootstrap card design and enhanced UX features.
 * Provides secure login functionality with password visibility controls and form validation.
 *
 * Features:
 * - Bootstrap card-based responsive design (mobile-friendly)
 * - Password visibility toggle with Bootstrap Icons
 * - Form validation support with 'needs-validation' class
 * - Autocomplete attributes for accessibility
 * - Development database info display (when available)
 * - CSRF protection via CakePHP Form helper
 *
 * Form Fields:
 * - username: Text input with autocomplete support
 * - password: Password input with visibility toggle
 *
 * JavaScript:
 * - DOMContentLoaded listener for password toggle functionality
 * - Bootstrap Icons toggle between 'bi-eye' and 'bi-eye-slash'
 *
 * Variables:
 * @var \App\View\AppView $this
 * @var array|null $dbInfo Optional database connection information for development
 */

$this->assign('title', 'Login'); ?>
<?php if (isset($dbInfo)) : ?>
<div style="background:#ffe;border:1px solid #cc0;padding:8px;margin-bottom:12px;">
    <strong>Database Info:</strong><br>
    Driver: <?= h($dbInfo['driver']) ?><br>
    Database: <?= h($dbInfo['database']) ?>
</div>
<?php endif; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Sign In</h2>
                    <?php $redirect = $this->getRequest()->getQuery('redirect'); ?>
                    <?= $this->Form->create(null, ['url' => ['controller' => 'Users', 'action' => 'login'], 'class' => 'needs-validation', 'novalidate' => true]) ?>
                    <?php if ($redirect): ?>
                    <?= $this->Form->hidden('redirect', ['value' => $redirect]) ?>
                    <?php endif; ?>
                    <div class="mb-3">
                        <?= $this->Form->control('username', [
                            'label' => ['text' => 'Username', 'class' => 'form-label'],
                            'type' => 'text',
                            'class' => 'form-control',
                            'required' => true,
                            'autocomplete' => 'username',
                        ]); ?>
                    </div>
                    <div class="mb-3">
                        <label for="user-password" class="form-label">Password</label>
                        <div class="input-group">
                            <?= $this->Form->control('password', [
                                'type' => 'password',
                                'id' => 'user-password',
                                'class' => 'form-control',
                                'label' => false,
                                'required' => true,
                                'autocomplete' => 'current-password',
                            ]) ?>
                            <span class="input-group-text p-0">
                                <button type="button" class="btn border-0 bg-transparent px-2" id="toggle-user-password"
                                    tabindex="-1" style="box-shadow:none;">
                                    <span class="bi bi-eye"></span>
                                </button>
                            </span>
                        </div>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var btn = document.getElementById('toggle-user-password');
                            var input = document.getElementById('user-password');
                            btn.addEventListener('click', function() {
                                input.type = input.type === 'password' ? 'text' : 'password';
                                btn.innerHTML = input.type === 'password' ?
                                    '<span class="bi bi-eye"></span>' :
                                    '<span class="bi bi-eye-slash"></span>';
                            });
                        });
                        </script>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <?= $this->Form->button(__('Login'), ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                    <div class="text-center">
                        <?= $this->Html->link(__('Forgot password?'), ['action' => 'resetPassword'], ['class' => 'btn btn-link']) ?>
                    </div>
                    <?= $this->Form->end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>