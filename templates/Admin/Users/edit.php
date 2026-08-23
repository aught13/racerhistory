<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<?php $this->assign('title', 'Edit User'); ?>
<div class="container py-4" data-controller="password-toggle">
    <h1 class="mb-4">Edit User</h1>
    <?= $this->Form->create($user, ['type' => 'file']) ?>
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
    <div class="row mt-3">
        <div class="col-md-6">
            <?= $this->Form->control('display_name', ['label' => 'Display Name', 'class' => 'form-control']) ?>
        </div>
        <div class="col-md-6">
            <?= $this->Form->control('website_url', ['label' => 'Website', 'class' => 'form-control']) ?>
        </div>
    </div>
    <div class="mb-3 mt-3">
        <?= $this->Form->control('bio', ['type' => 'textarea', 'rows' => 4, 'class' => 'form-control']) ?>
    </div>
    <div class="mb-3">
        <?= $this->Form->control('social_links', ['type' => 'textarea', 'rows' => 3, 'class' => 'form-control', 'label' => 'Social Links (one URL per line)', 'placeholder' => 'https://twitter.com/yourhandle']) ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Profile Image</label>
        <?php if (!empty($user->profile_image_id)) : ?>
            <div class="mb-2">
                <img src="<?= h($this->Url->build(['controller' => 'Images', 'action' => 'serve', $user->profile_image_id, '?' => ['profile' => 'roster_avatar']])) ?>" alt="<?= h($user->display_name ?: $user->username) ?>" class="img-thumbnail" style="max-width:150px;">
            </div>
        <?php endif; ?>
        <?= $this->Form->control('profile_image_id', ['type' => 'select', 'options' => $imagesList ?? [], 'empty' => 'Select existing image (or leave)', 'label' => 'Choose existing image', 'class' => 'form-select mb-2']) ?>

        <?= $this->Form->control('avatar', ['type' => 'file', 'label' => 'Upload Avatar', 'class' => 'form-control']) ?>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $this->Form->control('role_id', [
                'type' => 'select',
                'options' => $roleOptions ?? [],
                'empty' => 'No RBAC role',
                'label' => 'RBAC Role',
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
                'data-password-toggle-target' => 'input',
            ]) ?>
            <button type="button" class="btn btn-outline-secondary" id="toggle-admin-edit-password" tabindex="-1" data-password-toggle-target="button" data-action="password-toggle#toggle">
                <span class="bi bi-eye"></span>
            </button>
        </div>
    </div>
    <?= $this->Form->button('Update User', ['class' => 'btn btn-primary']) ?>
    <?= $this->Html->link('Cancel', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    <?= $this->Form->end() ?>
</div>
