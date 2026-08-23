<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 * @var array<string,array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}> $matrix
 * @var array<string,array<string,string>> $levelOptionsByModel
 * @var array<string,array{label:string,custom_rules:array<string,string>}> $modelDefinitions
 */

$this->assign('title', 'Edit Role Matrix');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-2">Edit <?= h($role->name) ?> Permissions</h1>
            <p class="text-muted mb-0">Use the matrix below to control read, create, update, delete, and custom rules for each module.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php
                echo $this->Form->create(null, [
                    'url' => ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'edit', $role->id],
                ]);
                $this->Form->unlockField('permissions');
                ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 180px;">Entity Name</th>
                            <th style="min-width: 220px;">Read Access</th>
                            <th style="min-width: 140px;">Create Allowed</th>
                            <th style="min-width: 220px;">Update Access</th>
                            <th style="min-width: 220px;">Delete Access</th>
                            <th style="min-width: 260px;">Special / Custom Rules</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelDefinitions as $modelName => $definition) : ?>
                            <?php
                                $row = $matrix[$modelName] ?? [];
                                $levelOptions = $levelOptionsByModel[$modelName] ?? [
                                    'none' => 'No',
                                    'all' => 'Yes',
                                ];
                                ?>
                            <tr>
                                <td>
                                    <strong><?= h($definition['label']) ?></strong>
                                </td>
                                <td>
                                    <?= $this->Form->radio("permissions.{$modelName}.can_read", $levelOptions, [
                                        'value' => $row['can_read'] ?? 'none',
                                        'legend' => false,
                                        'hiddenField' => false,
                                        'label' => ['class' => 'form-check-label'],
                                        'class' => 'form-check-input',
                                        'templates' => [
                                            'radioWrapper' => '<div class="form-check form-check-inline">{{label}}</div>',
                                            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
                                        ],
                                    ]) ?>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <?= $this->Form->checkbox("permissions.{$modelName}.can_create", ['checked' => !empty($row['can_create']), 'class' => 'form-check-input']) ?>
                                        <label class="form-check-label" for="permissions-<?= h(strtolower($modelName)) ?>-can-create">Allow create</label>
                                    </div>
                                </td>
                                <td>
                                    <?= $this->Form->radio("permissions.{$modelName}.can_update", $levelOptions, [
                                        'value' => $row['can_update'] ?? 'none',
                                        'legend' => false,
                                        'hiddenField' => false,
                                        'label' => ['class' => 'form-check-label'],
                                        'class' => 'form-check-input',
                                        'templates' => [
                                            'radioWrapper' => '<div class="form-check form-check-inline">{{label}}</div>',
                                            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
                                        ],
                                    ]) ?>
                                </td>
                                <td>
                                    <?= $this->Form->radio("permissions.{$modelName}.can_delete", $levelOptions, [
                                        'value' => $row['can_delete'] ?? 'none',
                                        'legend' => false,
                                        'hiddenField' => false,
                                        'label' => ['class' => 'form-check-label'],
                                        'class' => 'form-check-input',
                                        'templates' => [
                                            'radioWrapper' => '<div class="form-check form-check-inline">{{label}}</div>',
                                            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
                                        ],
                                    ]) ?>
                                </td>
                                <td>
                                    <?php if ($definition['custom_rules'] === []) : ?>
                                        <span class="text-muted small">No special rules.</span>
                                    <?php else : ?>
                                        <?php foreach ($definition['custom_rules'] as $ruleKey => $ruleLabel) : ?>
                                            <div class="form-check mb-2">
                                                <?= $this->Form->checkbox("permissions.{$modelName}.custom_rules.{$ruleKey}", ['checked' => !empty($row['custom_rules'][$ruleKey]), 'class' => 'form-check-input']) ?>
                                                <label class="form-check-label" for="permissions-<?= h(strtolower($modelName . '-' . $ruleKey)) ?>"><?= h($ruleLabel) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <?= $this->Form->button('Save Matrix', ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link('Back to Roles', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index'], ['class' => 'btn btn-outline-secondary', 'data-turbo-frame' => 'admin-content']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
