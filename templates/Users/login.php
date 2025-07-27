<?php $this->assign('title', 'Login'); ?>
<div class="users login">
    <h1>Login</h1>
    <?= $this->Form->create(null, ['url' => ['controller' => 'users', 'action' => 'login']]) ?>
    <fieldset>
        <legend><?= __('Please enter your username and password') ?></legend>
        <?= $this->Form->control('username', [
            'required' => true,
            'label' => 'Username',
            'type' => 'text',
            'autocomplete' => 'username',
        ]); ?>
        <?= $this->Form->control('password', [
            'required' => true,
            'type' => 'password',
            'label' => 'Password',
            'autocomplete' => 'current-password',
        ]); ?>
    </fieldset>
    <div style="display: flex; gap: 1em; align-items: center;">
        <?= $this->Form->button(__('Login')); ?>
        <?= $this->Html->link(__('Forgot password?'), ['action' => 'resetPassword'], ['class' => 'reset-password-link']); ?>
    </div>
    <?= $this->Form->end(); ?>
</div>
