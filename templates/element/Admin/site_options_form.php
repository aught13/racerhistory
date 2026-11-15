<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed,help?:string,options?:array<string,string>}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */
?>
<turbo-frame id="site_options_frame">
    <?= $this->Flash->render() ?>

    <?php
    $helpFor = static function (string $optionKey, array $definition): string {
        $help = trim((string)($definition['help'] ?? ''));

        return $help !== '' ? $help : 'Enter or choose the value used by this site setting.';
    };
    $renderHelp = static function (string $help): void {
        echo '<div class="form-text">' . h($help) . '</div>';
    };
    ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">Global Site Settings</h3>
            <?php if ($this->Rbac->can('Roles', 'read')) : ?>
                <?= $this->Html->link('Manage RBAC Roles', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index'], ['class' => 'btn btn-sm btn-outline-secondary float-end', 'data-turbo-frame' => 'admin-content']) ?>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?= $this->Form->create(null, [
                'url' => ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit'],
                'templates' => [
                    'inputContainer' => '<div class="mb-3">{{content}}</div>',
                    'label' => '<label{{attrs}} class="form-label">{{text}}</label>',
                    'inputContainerError' => '<div class="mb-3">{{content}}<div class="invalid-feedback d-block">{{error}}</div></div>',
                ],
                'data-turbo-frame' => 'site_options_frame',
            ]) ?>

            <fieldset class="mb-4">
                <legend class="h4">General Site Settings</legend>
                <p class="text-muted">Configure site-wide behavior. Each setting includes its completion instructions below the control.</p>

            <?php foreach ($siteOptionDefinitions as $optionKey => $definition) : ?>
                <?php
                $type = $definition['type'];
                $label = $definition['label'];
                $value = $siteOptions[$optionKey] ?? ($definition['default'] ?? null);
                $help = $helpFor($optionKey, $definition);
                ?>

                <?php if ($optionKey === 'role_privileges') : ?>
                    <div class="mb-3">
                        <label class="form-label"><?= h($label) ?></label>
                        <div class="small text-muted mb-2">Role privileges are now managed in the dedicated RBAC role matrix.</div>
                        <?php $renderHelp($help); ?>
                        <?php if ($this->Rbac->can('Roles', 'read')) : ?>
                            <div class="d-grid gap-2 col-6 p-0">
                                <?= $this->Html->link('Manage RBAC Roles', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index'], ['class' => 'btn btn-primary btn-lg', 'data-turbo-frame' => 'admin-content']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($type === 'checkbox') : ?>
                    <?= $this->Form->control($optionKey, [
                        'type' => 'checkbox',
                        'label' => $label,
                        'checked' => (bool)$value,
                        'class' => 'form-check-input',
                        'required' => false,
                        'templates' => [
                            'inputContainer' => '<div class="mb-3">{{content}}</div>',
                            'checkboxWrapper' => '<div class="form-check form-switch">{{label}}</div>',
                            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}} class="form-check-label">{{text}}</label>',
                        ],
                    ]) ?>
                    <?php $renderHelp($help); ?>
                <?php else : ?>
                    <?php
                    $inputType = in_array($type, ['number', 'email', 'textarea'], true) ? $type : 'text';
                    $controlOptions = [
                        'type' => $inputType,
                        'label' => $label,
                        'value' => $value,
                        'class' => 'form-control',
                        'required' => false,
                    ];

                    if ($inputType === 'number') {
                        $controlOptions['step'] = 1;
                        $controlOptions['min'] = 1;
                    }

                    if ($inputType === 'textarea') {
                        $controlOptions['rows'] = 4;
                    }
                    ?>
                    <?= $this->Form->control($optionKey, $controlOptions) ?>
                    <?php $renderHelp($help); ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </fieldset>

            <div class="d-flex gap-2 mt-3">
                <?= $this->Form->button('Save Settings', ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link(
                    'Reload',
                    ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit'],
                    ['class' => 'btn btn-outline-secondary', 'data-turbo-frame' => 'site_options_frame'],
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</turbo-frame>
