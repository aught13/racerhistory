<?php
/**
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Reset Password'); ?>
<div class="users reset-password">
    <h1>Reset Password</h1>
    <?= $this->Form->create(null); ?>
    <fieldset>
        <legend><?= __('Enter your email address') ?></legend>
        <?= $this->Form->control('email', ['required' => true, 'type' => 'email', 'label' => 'Email Address']); ?>
    </fieldset>
    <?= $this->Form->button(__('Send Reset Link')); ?>
    <?= $this->Form->end(); ?>
</div>
