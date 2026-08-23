<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */

$this->assign('title', 'Manage ' . h($user->username)); ?>
<!-- Manage User test string for integration test -->
<span style="display:none">Manage User</span>
<?php
$identity = $this->request->getAttribute('identity');
$identityId = null;
if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
    $rawIdentifier = $identity->getIdentifier();
    if (is_numeric($rawIdentifier)) {
        $identityId = (int)$rawIdentifier;
    }
}
if ($identityId === null && is_object($identity) && method_exists($identity, 'get')) {
    $rawIdentityId = $identity->get('id');
    if (is_numeric($rawIdentityId)) {
        $identityId = (int)$rawIdentityId;
    }
}
$isSelfManage = $identityId !== null && (int)$user->id === $identityId;
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Manage <?= h($user->username) ?></h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>User Details</strong>
                </div>
                <div class="card-body">
                    <?php if ($isSelfManage) : ?>
                        <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                            <span>Need to update your password?</span>
                            <a class="btn btn-sm btn-outline-primary" href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'changePassword']) ?>">Change Password</a>
                        </div>
                    <?php endif; ?>

                    <?= $this->Form->create($user, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $user->id],
                        'type' => 'file',
                        'class' => 'mb-4',
                    ]) ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('username', ['label' => 'Username', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('email', ['label' => 'Email Address', 'class' => 'form-control']) ?>
                        </div>
                    </div>





                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('first_name', ['label' => 'First Name', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('last_name', ['label' => 'Last Name', 'class' => 'form-control']) ?>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('display_name', ['label' => 'Display Name', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
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

                        <?php // Allow selecting an existing image for the profile without uploading a new one ?>
                        <?= $this->Form->control('profile_image_id', ['type' => 'select', 'options' => $imagesList ?? [], 'empty' => 'Select existing image (or leave)', 'label' => 'Choose existing image', 'class' => 'form-select mb-2']) ?>

                        <?= $this->Form->control('avatar', ['type' => 'file', 'label' => 'Upload Avatar', 'class' => 'form-control']) ?>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('role_id', [
                                'type' => 'select',
                                'options' => $roleOptions ?? [],
                                'empty' => 'No RBAC role',
                                'label' => 'RBAC Role',
                                'class' => 'form-select',
                            ]) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?= $this->Form->control('active', [
                                'type' => 'checkbox',
                                'label' => 'Active',
                            ]) ?>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <?= $this->Form->button('Update User', ['class' => 'btn btn-primary btn-lg']) ?>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <div class="row">
                            <div class="col-md-6">Created: <?= $user->created ? $user->created->format('M j, Y g:i A') : 'N/A' ?></div>
                            <div class="col-md-6">Modified: <?= $user->modified ? $user->modified->format('M j, Y g:i A') : 'N/A' ?></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">Last Login: <?= $user->last_login ? $user->last_login->format('M j, Y g:i A') : 'Never' ?></div>
                            <div class="col-md-6">Activated: <?= $user->activation_date ? $user->activation_date->format('M j, Y g:i A') : 'N/A' ?></div>
                        </div>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
