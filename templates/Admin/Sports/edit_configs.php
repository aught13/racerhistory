<?php
/**
 * @var \App\View\AppView $this
 * @var array $configs
 * @var string $sportKey
 * @var string $sportDisplayName
 * @var string $routeSportRef
 * @var array<string,string> $sportOptions
 * @var string|null $configController
 */

$periodIndex = count($configs['period_names']);
$settingIndex = count($configs['settings']);
$periodRowIndex = 0;
$configController = $configController ?? 'SiteOptions';
$viewAction = $configController === 'SiteOptions' ? 'sportsConfigs' : 'configs';
$editAction = $configController === 'SiteOptions' ? 'editSportConfigs' : 'editConfigs';
$resetAction = $configController === 'SiteOptions' ? 'resetSportConfigs' : 'resetConfigs';
$backRoute = $configController === 'SiteOptions'
    ? ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit']
    : ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'index'];
$protectedSettingKeys = [
    'sport_active',
    'has_stats',
    'stats_tracked',
    'default_periods',
    'supports_periods',
    'overtime_name',
    'scoring_type',
];
?>

<div data-controller="sports-configs-form"
    data-sports-configs-form-period-name-index-value="<?= $periodIndex ?>"
    data-sports-configs-form-setting-index-value="<?= $settingIndex ?>">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= __('Edit Sport Configurations') ?></h2>
        <p class="text-muted">Configure period names, officials, and settings for <?= h($sportDisplayName) ?></p>
    </div>
    <div>
        <?= $this->Html->link(
            __('View Configurations'),
            ['prefix' => 'Admin', 'controller' => $configController, 'action' => $viewAction, $routeSportRef],
            ['class' => 'btn btn-info me-2'],
        ) ?>
        <?= $this->Html->link(__('Back'), $backRoute, ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<?php if (!empty($sportOptions)) : ?>
<div class="mb-3">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($sportOptions as $optionKey => $optionLabel) : ?>
            <?php $isActive = $optionKey === $sportKey; ?>
            <?= $this->Html->link(
                $optionLabel,
                ['prefix' => 'Admin', 'controller' => $configController, 'action' => $editAction, $optionKey],
                ['class' => 'btn btn-sm ' . ($isActive ? 'btn-primary' : 'btn-outline-primary')],
            ) ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= $this->Form->create(null, [
    'url' => ['prefix' => 'Admin', 'controller' => $configController, 'action' => $editAction, $routeSportRef],
    'type' => 'post',
]) ?>

<div class="row">
    <!-- Period Names -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?= __('Period Names') ?></h5>
                <button type="button" class="btn btn-sm btn-outline-primary"
                    data-action="click->sports-configs-form#addPeriodName">
                    <i class="fas fa-plus me-1"></i><?= __('Add') ?>
                </button>
            </div>
            <div class="card-body">
                <div id="period-names-container" data-sports-configs-form-target="periodNamesContainer">
                    <?php foreach ($configs['period_names'] as $periods => $config) : ?>
                    <div class="period-name-row mb-3" data-index="<?= $periodRowIndex ?>">
                        <div class="row">
                            <div class="col-3">
                                <?= $this->Form->control("configs.period_name_{$periods}.periods", [
                                    'label' => false,
                                    'value' => $periods,
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => '# periods',
                                    'type' => 'number',
                                    'min' => 1,
                                    'max' => 20,
                                ]) ?>
                            </div>
                            <div class="col-6">
                                <?= $this->Form->control("configs.period_name_{$periods}.value", [
                                    'label' => false,
                                    'value' => $config['value'],
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => 'Period name (Half, Quarter, etc.)',
                                ]) ?>
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-action="click->sports-configs-form#removePeriodName">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-12">
                                <?= $this->Form->control("configs.period_name_{$periods}.description", [
                                    'label' => false,
                                    'value' => $config['description'],
                                    'class' => 'form-control form-control-sm text-muted',
                                    'placeholder' => 'Description (optional)',
                                ]) ?>
                            </div>
                        </div>
                    </div>
                        <?php $periodRowIndex++; ?>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted">
                    Define period names for different period counts. E.g., 2 periods = "Half", 4 periods = "Quarter"
                </small>
            </div>
        </div>
    </div>

    <!-- Officials -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= __('Officials') ?></h5>
            </div>
            <div class="card-body">
                <?= $this->Form->control('configs.officials.value', [
                    'label' => __('Official Titles'),
                    'value' => is_array($configs['officials']['value']) ? implode(', ', $configs['officials']['value']) : ($configs['officials']['value'] ?? ''),
                    'class' => 'form-control',
                    'placeholder' => 'Referee 1, Referee 2, Official 3',
                    'help' => 'Separate multiple officials with commas',
                ]) ?>

                <?= $this->Form->control('configs.officials.description', [
                    'label' => __('Description'),
                    'value' => $configs['officials']['description'] ?? '',
                    'class' => 'form-control',
                    'placeholder' => 'Description of officials for this sport',
                ]) ?>
            </div>
        </div>
    </div>
</div>

<!-- Other Settings -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?= __('Other Settings') ?></h5>
                <button type="button" class="btn btn-sm btn-outline-primary"
                    data-action="click->sports-configs-form#addSetting">
                    <i class="fas fa-plus me-1"></i><?= __('Add Setting') ?>
                </button>
            </div>
            <div class="card-body">
                <div id="settings-container" data-sports-configs-form-target="settingsContainer">
                    <?php foreach ($configs['settings'] as $key => $config) : ?>
                    <div class="setting-row mb-3">
                        <div class="row">
                            <div class="col-3">
                                <?= $this->Form->control("configs.{$key}.key", [
                                    'label' => false,
                                    'value' => $key,
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => 'Setting key',
                                    'readonly' => in_array($key, $protectedSettingKeys, true),
                                ]) ?>
                            </div>
                            <div class="col-6">
                                <?php if ($key === 'supports_periods') : ?>
                                    <?= $this->Form->control("configs.{$key}.value", [
                                        'label' => false,
                                        'value' => is_array($config['value']) ? implode(', ', $config['value']) : $config['value'],
                                        'class' => 'form-control form-control-sm',
                                        'placeholder' => 'Any or comma-separated numbers (e.g., any or 2, 4)',
                                    ]) ?>
                                <?php else : ?>
                                    <?php
                                    $settingValue = is_array($config['value'])
                                        ? json_encode($config['value'], JSON_UNESCAPED_SLASHES)
                                        : $config['value'];
                                    if (is_bool($settingValue)) {
                                        $settingValue = $settingValue ? 'true' : 'false';
                                    }
                                    ?>
                                    <?= $this->Form->control("configs.{$key}.value", [
                                        'label' => false,
                                        'value' => $settingValue,
                                        'class' => 'form-control form-control-sm',
                                        'placeholder' => 'Setting value',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-2">
                                <?php if (!in_array($key, $protectedSettingKeys, true)) : ?>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-action="click->sports-configs-form#removeSetting">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-12">
                                <?= $this->Form->control("configs.{$key}.description", [
                                    'label' => false,
                                    'value' => $config['description'],
                                    'class' => 'form-control form-control-sm text-muted',
                                    'placeholder' => 'Description (optional)',
                                ]) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <?= $this->Form->button(__('Save Configurations'), ['class' => 'btn btn-success me-2']) ?>
        <?= $this->Html->link(
            __('Cancel'),
            ['prefix' => 'Admin', 'controller' => $configController, 'action' => $viewAction, $routeSportRef],
            ['class' => 'btn btn-secondary'],
        ) ?>
    </div>
    <div>
        <?= $this->Form->postLink(
            __('Reset to Defaults'),
            ['prefix' => 'Admin', 'controller' => $configController, 'action' => $resetAction, $routeSportRef],
            [
                'class' => 'btn btn-outline-warning',
                'confirm' => __('Are you sure you want to reset all configurations to defaults? This action cannot be undone.'),
            ],
        ) ?>
    </div>
</div>

<?= $this->Form->end() ?>
</div>
