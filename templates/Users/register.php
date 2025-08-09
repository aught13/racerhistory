<?php
/**
 * User Registration Template
 *
 * User registration form with entity binding and client-side password visibility controls.
 * Provides new user account creation with form validation and Bootstrap styling.
 *
 * Features:
 * - User entity form binding for automatic data population
 * - Password visibility toggle with Bootstrap Icons
 * - Required field validation
 * - Bootstrap form styling with input groups
 * - CSRF protection via CakePHP Form helper
 * - Internationalization support with __() function
 *
 * Form Fields:
 * - username: Required text input
 * - email: Required email input with validation
 * - password: Required password input with visibility toggle
 *
 * JavaScript:
 * - DOMContentLoaded listener for password toggle
 * - Toggle between 'bi-eye' and 'bi-eye-slash' icons
 * - Password type switching (password/text)
 *
 * Variables:
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user User entity for form binding
 */

$this->assign('title', 'Register'); ?>
<div class="users register">
    <h1>Register</h1>
    <?= $this->Form->create($user); ?>
    <fieldset>
        <legend><?= __('Please fill in your details') ?></legend>
        <?= $this->Form->control('username', ['required' => true, 'label' => 'Username']); ?>
        <?= $this->Form->control('email', ['required' => true, 'type' => 'email', 'label' => 'Email Address']); ?>
        <div class="input-group mb-3">
            <?= $this->Form->control('password', ['required' => true, 'type' => 'password', 'label' => 'Password', 'id' => 'register-password']); ?>
            <button type="button" class="btn btn-outline-secondary" id="toggle-register-password" tabindex="-1">
                <span class="bi bi-eye"></span>
            </button>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('toggle-register-password');
            var input = document.getElementById('register-password');
            btn.addEventListener('click', function() {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.innerHTML = input.type === 'password' ? '<span class="bi bi-eye"></span>' : '<span class="bi bi-eye-slash"></span>';
            });
        });
        </script>
    </fieldset>
    <?= $this->Form->button(__('Register')); ?>
    <?= $this->Form->end(); ?>
</div>
