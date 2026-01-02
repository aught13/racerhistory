<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Sport $sport
 * @var array $configs
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= __('Sport Configurations') ?></h2>
        <p class="text-muted">Configure period names, officials, and settings for <?= h($sport->sport_name) ?></p>
    </div>
    <div>
        <?= $this->Html->link(__('Edit Configurations'), ['action' => 'editConfigs', $sport->id], ['class' => 'btn btn-primary me-2']) ?>
        <?= $this->Html->link(__('Back to Sports'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<div class="row">
    <!-- Period Names -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= __('Period Names') ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($configs['period_names'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><?= __('Periods') ?></th>
                                    <th><?= __('Name') ?></th>
                                    <th><?= __('Description') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($configs['period_names'] as $periods => $config): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= h($periods) ?></span></td>
                                    <td><strong><?= h($config['value']) ?></strong></td>
                                    <td class="text-muted small"><?= h($config['description']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?= __('No period names configured.') ?></p>
                <?php endif; ?>
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
                <?php if (!empty($configs['officials']['value'])): ?>
                    <ul class="list-unstyled">
                        <?php
                        $officials = $configs['officials']['value'];
                        if (is_string($officials)) {
                            $officials = array_filter(array_map('trim', explode(',', $officials)));
                        }
                        foreach ($officials as $official): ?>
                        <li class="mb-1">
                            <i class="fas fa-user-tie text-muted me-2"></i>
                            <?= h($official) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!empty($configs['officials']['description'])): ?>
                        <p class="text-muted small mt-2"><?= h($configs['officials']['description']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted"><?= __('No officials configured.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Other Settings -->
<?php if (!empty($configs['settings'])): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= __('Other Settings') ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?= __('Setting') ?></th>
                                <th><?= __('Value') ?></th>
                                <th><?= __('Description') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs['settings'] as $key => $config): ?>
                            <tr>
                                <td><code><?= h($key) ?></code></td>
                                <td>
                                    <?php if (is_array($config['value'])): ?>
                                        <span class="badge bg-info"><?= implode(', ', $config['value']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= h($config['value']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= h($config['description']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Help Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= __('Configuration Help') ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><?= __('Period Names') ?></h6>
                        <p class="small text-muted">
                            Define names for different period counts:<br>
                            • <strong>2 periods</strong>: "Half" (Basketball halves)<br>
                            • <strong>4 periods</strong>: "Quarter" (Basketball/Football quarters)<br>
                            • <strong>9 periods</strong>: "Inning" (Baseball innings)
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6><?= __('Officials') ?></h6>
                            <p class="small text-muted">
                            List of officiating roles for this sport:<br>
                            • Basketball: Crew Chief, Referee, Umpire<br>
                            • Football: Referee, Umpire, Line Judge<br>
                            • Baseball: Home Plate, First Base, Second Base
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6><?= __('Settings') ?></h6>
                        <p class="small text-muted">
                            Other sport-specific configurations:<br>
                            • <strong>default_periods</strong>: Default period count<br>
                            • <strong>supports_periods</strong>: Valid period counts<br>
                            • <strong>scoring_type</strong>: cumulative or by_period
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
