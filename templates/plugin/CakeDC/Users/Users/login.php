<?php
/**
 * CakeDC/Users login template override.
 *
 * Goal: Keep CakeDC/Users controller/action for authentication, but present UI
 * consistent with the app (Bootstrap 5) and route password reset to the app's
 * existing reset-password flow.
 *
 * @var \App\View\AppView $this
 */

declare(strict_types=1);

use Cake\Core\Configure;

$redirect = (string)($this->getRequest()->getQuery('redirect') ?? '');
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <h1 class="h3 mb-3">Login</h1>

            <?= $this->Flash->render('auth') ?>

            <?= $this->Form->create(null, ['class' => 'card card-body shadow-sm']) ?>
                <fieldset>
                    <legend class="visually-hidden"><?= __d('cake_d_c/users', 'Login') ?></legend>

                    <?php if ($redirect !== '') : ?>
                        <?= $this->Form->control('redirect', ['type' => 'hidden', 'value' => $redirect]) ?>
                    <?php endif; ?>

                    <?= $this->Form->control('username', [
                        'label' => ['text' => __d('cake_d_c/users', 'Username'), 'class' => 'form-label'],
                        'required' => true,
                        'class' => 'form-control',
                        'autocomplete' => 'username',
                    ]) ?>

                    <?= $this->Form->control('password', [
                        'label' => ['text' => __d('cake_d_c/users', 'Password'), 'class' => 'form-label'],
                        'required' => true,
                        'class' => 'form-control',
                        'autocomplete' => 'current-password',
                    ]) ?>

                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <?php
                        $registrationActive = (bool)Configure::read('Users.Registration.active');
                        if ($registrationActive) {
                            echo $this->Html->link(__d('cake_d_c/users', 'Register'), ['action' => 'register'], ['class' => 'link-secondary']);
                        }

                        if ((bool)Configure::read('Users.Email.required')) {
                            echo $this->Html->link(
                                __d('cake_d_c/users', 'Reset Password'),
                                ['plugin' => null, 'controller' => 'Users', 'action' => 'resetPassword'],
                                ['class' => 'link-secondary'],
                            );
                        }
                        ?>
                    </div>
                </fieldset>

                <div class="mt-3">
                    <?= $this->Form->button(__d('cake_d_c/users', 'Login'), ['class' => 'btn btn-primary w-100']) ?>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
