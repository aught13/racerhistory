<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */
?>
<turbo-frame id="site_options_frame">
    <?= $this->Flash->render() ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">Global Site Settings</h3>
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

            <?php foreach ($siteOptionDefinitions as $optionKey => $definition) : ?>
                <?php
                $type = $definition['type'];
                $label = $definition['label'];
                $value = $siteOptions[$optionKey] ?? ($definition['default'] ?? null);
                ?>

                <?php if ($type === 'checkbox') : ?>
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
                <?php else : ?>
                    <?php
                    $inputType = in_array($type, ['number', 'email'], true) ? $type : 'text';
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
                    ?>
                    <?= $this->Form->control($optionKey, $controlOptions) ?>
                <?php endif; ?>
            <?php endforeach; ?>

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
