<?php $this->assign('title', 'Register'); ?>
<div class="users register">
    <h1>Register</h1>
    <?= $this->Form->create($user); ?>
    <fieldset>
        <legend><?= __('Please fill in your details') ?></legend>
        <?= $this->Form->control('username', ['required' => true, 'label' => 'Username']); ?>
        <?= $this->Form->control('email', ['required' => true, 'type' => 'email', 'label' => 'Email Address']); ?>
        <?= $this->Form->control('password', ['required' => true, 'type' => 'password', 'label' => 'Password']); ?>
    </fieldset>
    <?= $this->Form->button(__('Register')); ?>
    <?= $this->Form->end(); ?>
</div>