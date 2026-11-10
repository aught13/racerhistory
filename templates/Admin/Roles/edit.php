<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 * @var array<string,array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}> $matrix
 * @var array<string,array<string,string>> $levelOptionsByModel
 * @var array<string,array{label:string,custom_rules:array<string,string>}> $modelDefinitions
 * @var string $roleScopeMode
 * @var array<int,string> $selectedSportKeys
 * @var array<int,int> $selectedTeamIds
 * @var array<string,string> $scopeSportOptions
 * @var array<int,array{label:string,sportKey:string}> $scopeTeamOptions
 * @var bool $roleIsGlobalAdmin
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
                $this->Form->unlockField('data_scope');
                ?>

            <section class="mb-4" aria-labelledby="role-data-scope-heading">
                <h2 id="role-data-scope-heading" class="h5">Team-Owned Data Scope</h2>
                <?php if ($roleIsGlobalAdmin) : ?>
                    <div class="alert alert-info mb-0">The Admin role is globally unrestricted and cannot be limited by sport or team.</div>
                <?php else : ?>
                    <fieldset class="mb-3">
                        <legend class="form-label">Access scope</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="data_scope[mode]" id="data-scope-all" value="all"<?= $roleScopeMode === 'all' ? ' checked' : '' ?>>
                            <label class="form-check-label" for="data-scope-all">All sports and teams</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="data_scope[mode]" id="data-scope-selected" value="selected"<?= $roleScopeMode === 'selected' ? ' checked' : '' ?>>
                            <label class="form-check-label" for="data-scope-selected">Selected sports and teams</label>
                        </div>
                        <div class="form-text">Choose all access or limit this role to the grants below. A selected scope with no grants denies team-owned records.</div>
                    </fieldset>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="data-scope-sports">Allowed sports</label>
                            <select class="form-select" id="data-scope-sports" name="data_scope[sport_keys][]" multiple size="5">
                                <?php foreach ($scopeSportOptions as $sportKey => $sportName) : ?>
                                    <option value="<?= h($sportKey) ?>"<?= in_array($sportKey, $selectedSportKeys, true) ? ' selected' : '' ?>><?= h($sportName) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select a sport to grant access to every team and team season in that sport.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="data-scope-teams">Allowed teams</label>
                            <select class="form-select" id="data-scope-teams" name="data_scope[team_ids][]" multiple size="5">
                                <?php foreach ($scopeTeamOptions as $teamId => $teamOption) : ?>
                                    <option value="<?= (int)$teamId ?>"<?= in_array((int)$teamId, $selectedTeamIds, true) ? ' selected' : '' ?>><?= h($teamOption['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select individual teams for narrower access. Team-only grants do not allow creating additional teams.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

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
