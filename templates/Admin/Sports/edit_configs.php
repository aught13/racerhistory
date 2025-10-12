<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Sport $sport
 * @var array $configs
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= __('Edit Sport Configurations') ?></h2>
        <p class="text-muted">Configure period names, officials, and settings for <?= h($sport->sport_name) ?></p>
    </div>
    <div>
        <?= $this->Html->link(__('View Configurations'), ['action' => 'configs', $sport->id], ['class' => 'btn btn-info me-2']) ?>
        <?= $this->Html->link(__('Back to Sports'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<?= $this->Form->create(null, [
    'url' => ['controller' => 'Sports', 'action' => 'editConfigs', $sport->id],
    'type' => 'post'
]) ?>

<div class="row">
    <!-- Period Names -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?= __('Period Names') ?></h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPeriodName()">
                    <i class="fas fa-plus me-1"></i><?= __('Add') ?>
                </button>
            </div>
            <div class="card-body">
                <div id="period-names-container">
                    <?php $periodIndex = 0; ?>
                    <?php foreach ($configs['period_names'] as $periods => $config): ?>
                    <div class="period-name-row mb-3" data-index="<?= $periodIndex ?>">
                        <div class="row">
                            <div class="col-3">
                                <?= $this->Form->control("configs.period_name_{$periods}.periods", [
                                    'label' => false,
                                    'value' => $periods,
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => '# periods',
                                    'type' => 'number',
                                    'min' => 1,
                                    'max' => 20
                                ]) ?>
                            </div>
                            <div class="col-6">
                                <?= $this->Form->control("configs.period_name_{$periods}.value", [
                                    'label' => false,
                                    'value' => $config['value'],
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => 'Period name (Half, Quarter, etc.)'
                                ]) ?>
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePeriodName(this)">
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
                                    'placeholder' => 'Description (optional)'
                                ]) ?>
                            </div>
                        </div>
                    </div>
                    <?php $periodIndex++; ?>
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
                    'help' => 'Separate multiple officials with commas'
                ]) ?>

                <?= $this->Form->control('configs.officials.description', [
                    'label' => __('Description'),
                    'value' => $configs['officials']['description'] ?? '',
                    'class' => 'form-control',
                    'placeholder' => 'Description of officials for this sport'
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
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSetting()">
                    <i class="fas fa-plus me-1"></i><?= __('Add Setting') ?>
                </button>
            </div>
            <div class="card-body">
                <div id="settings-container">
                    <?php foreach ($configs['settings'] as $key => $config): ?>
                    <div class="setting-row mb-3">
                        <div class="row">
                            <div class="col-3">
                                <?= $this->Form->control("configs.{$key}.key", [
                                    'label' => false,
                                    'value' => $key,
                                    'class' => 'form-control form-control-sm',
                                    'placeholder' => 'Setting key',
                                    'readonly' => in_array($key, ['default_periods', 'supports_periods', 'overtime_name', 'scoring_type'])
                                ]) ?>
                            </div>
                            <div class="col-6">
                                <?php if ($key === 'supports_periods'): ?>
                                    <?= $this->Form->control("configs.{$key}.value", [
                                        'label' => false,
                                        'value' => is_array($config['value']) ? implode(', ', $config['value']) : $config['value'],
                                        'class' => 'form-control form-control-sm',
                                        'placeholder' => 'Comma-separated numbers (e.g., 2, 4)'
                                    ]) ?>
                                <?php else: ?>
                                    <?= $this->Form->control("configs.{$key}.value", [
                                        'label' => false,
                                        'value' => $config['value'],
                                        'class' => 'form-control form-control-sm',
                                        'placeholder' => 'Setting value'
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-2">
                                <?php if (!in_array($key, ['default_periods', 'supports_periods', 'overtime_name', 'scoring_type'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSetting(this)">
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
                                    'placeholder' => 'Description (optional)'
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
        <?= $this->Html->link(__('Cancel'), ['action' => 'configs', $sport->id], ['class' => 'btn btn-secondary']) ?>
    </div>
    <div>
        <?= $this->Form->postLink(__('Reset to Defaults'),
            ['action' => 'resetConfigs', $sport->id],
            [
                'class' => 'btn btn-outline-warning',
                'confirm' => __('Are you sure you want to reset all configurations to defaults? This action cannot be undone.')
            ]
        ) ?>
    </div>
</div>

<?= $this->Form->end() ?>

<script>
let periodNameIndex = <?= $periodIndex ?>;
let settingIndex = <?= count($configs['settings']) ?>;

function addPeriodName() {
    const container = document.getElementById('period-names-container');
    const newRow = document.createElement('div');
    newRow.className = 'period-name-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-3">
                <input type="number" name="configs[period_name_new_${periodNameIndex}][periods]"
                       class="form-control form-control-sm" placeholder="# periods" min="1" max="20">
            </div>
            <div class="col-6">
                <input type="text" name="configs[period_name_new_${periodNameIndex}][value]"
                       class="form-control form-control-sm" placeholder="Period name (Half, Quarter, etc.)">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePeriodName(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-12">
                <input type="text" name="configs[period_name_new_${periodNameIndex}][description]"
                       class="form-control form-control-sm text-muted" placeholder="Description (optional)">
            </div>
        </div>
    `;
    container.appendChild(newRow);
    periodNameIndex++;
}

function removePeriodName(button) {
    button.closest('.period-name-row').remove();
}

function addSetting() {
    const container = document.getElementById('settings-container');
    const newRow = document.createElement('div');
    newRow.className = 'setting-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-3">
                <input type="text" name="configs[new_setting_${settingIndex}][key]"
                       class="form-control form-control-sm" placeholder="Setting key">
            </div>
            <div class="col-6">
                <input type="text" name="configs[new_setting_${settingIndex}][value]"
                       class="form-control form-control-sm" placeholder="Setting value">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSetting(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-12">
                <input type="text" name="configs[new_setting_${settingIndex}][description]"
                       class="form-control form-control-sm text-muted" placeholder="Description (optional)">
            </div>
        </div>
    `;
    container.appendChild(newRow);
    settingIndex++;
}

function removeSetting(button) {
    button.closest('.setting-row').remove();
}
</script>
