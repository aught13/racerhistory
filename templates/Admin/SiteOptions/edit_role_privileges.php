<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array<string>> $privileges
 */

$this->assign('title', 'Role Privileges');
?>

<turbo-frame id="site_options_frame">
    <div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-2">Role Privileges</h1>
            <p class="text-muted mb-0">Edit the dynamic RBAC mapping. Enter comma-separated privilege tokens for each role.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->Form->create(null, [
                'url' => ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'editRolePrivileges'],
                'templates' => [
                    'inputContainer' => '<div class="mb-3">{{content}}</div>',
                    'label' => '<label{{attrs}} class="form-label">{{text}}</label>',
                ],
                'data-turbo-frame' => 'site_options_frame',
            ]) ?>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Privileges (comma-separated)</th>
                        <th class="text-center">Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($privileges as $role => $perms) :
                        $value = implode(', ', $perms);
                        ?>
                        <tr>
                            <td class="align-middle">
                                <?= h($role) ?>
                            </td>
                            <td>
                                <?= $this->Form->control("privileges[{$role}]", [
                                    'type' => 'text',
                                    'value' => $value,
                                    'label' => false,
                                    'class' => 'form-control',
                                    'required' => false,
                                ]) ?>
                            </td>
                            <td class="text-center align-middle">
                                <?= $this->Form->checkbox("delete_role[{$role}]", ['value' => 1, 'hiddenField' => false]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>
                            <?= $this->Form->control('new_role', ['type' => 'text', 'label' => false, 'placeholder' => 'Add role (e.g. contributor)', 'class' => 'form-control']) ?>
                        </td>
                        <td>
                            <?= $this->Form->control('new_privileges', ['type' => 'text', 'label' => false, 'placeholder' => 'comma,separated,privileges', 'class' => 'form-control']) ?>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex gap-2 mt-3">
                <?= $this->Form->button('Save', ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link('Back to Site Options', ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
    </div>
</turbo-frame>
