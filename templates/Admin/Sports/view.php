<?php
/**
 * @var \App\View\AppView $this
 * @var array $configs
 * @var \App\Model\Entity\Sport $sport
 * @var string|null $sportKey
 */
?>
<?php $this->assign('title', 'View Sport'); ?>
<?php $sportConfigRef = $sportKey ?? (string)$sport->id; ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?= h($sport->sport_name) ?> Details</h2>
                    <div class="btn-group" role="group">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'editConfigs', $sportConfigRef]) ?>" class="btn btn-warning btn-sm" title="Configure period names, officials, and settings">
                            <i class="fas fa-cog"></i> Config
                        </a>
                        <?php $teamCount = isset($sport->teams) ? count($sport->teams) : 0; ?>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                            data-item-type="sport"
                            data-associated='<?= json_encode([['label' => 'Teams', 'count' => $teamCount]]) ?>'>
                            Delete (<?= $teamCount ?>)
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th>Sport Name:</th>
                                <td>
                                    <?= h($sport->sport_name) ?>
                                    <small class="text-muted d-block">Sport name (unique, max 162 characters)</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Sport Configurations Section -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Sport Configurations</h4>
                            <div>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'configs', $sportConfigRef]) ?>"
                                    class="btn btn-info btn-sm me-2">
                                    <i class="fas fa-eye"></i> View All Configs
                                </a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'editConfigs', $sportConfigRef]) ?>"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-cog"></i> Edit Configurations
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($configs['period_names']) || !empty($configs['officials']['value']) || !empty($configs['settings'])) : ?>
                            <div class="row">
                                <!-- Period Names -->
                                <?php if (!empty($configs['period_names'])) : ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-clock me-1"></i>
                                                Period Names
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    <?php foreach ($configs['period_names'] as $periods => $config) : ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-secondary"><?= h($periods) ?> periods</span>
                                                        </td>
                                                        <td>
                                                            <strong><?= h($config['value']) ?></strong>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Officials -->
                                <?php if (!empty($configs['officials']['value'])) : ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-user-tie me-1"></i>
                                                Officials
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($configs['officials']['value'] as $official) : ?>
                                                <li class="mb-1">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <?= h($official) ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Other Settings -->
                            <?php if (!empty($configs['settings'])) : ?>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-cogs me-1"></i>
                                                Other Settings
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach (array_chunk($configs['settings'], 3, true) as $settingsChunk) : ?>
                                                    <?php foreach ($settingsChunk as $key => $config) : ?>
                                                    <div class="col-md-4 mb-2">
                                                        <div class="d-flex align-items-center">
                                                            <code class="me-2"><?= h($key) ?>:</code>
                                                            <?php if (is_array($config['value'])) : ?>
                                                                <?php
                                                                $renderedValues = [];
                                                                foreach ($config['value'] as $settingValue) {
                                                                    $renderedValues[] = is_scalar($settingValue) || $settingValue === null
                                                                        ? (string)$settingValue
                                                                        : (string)json_encode($settingValue, JSON_UNESCAPED_SLASHES);
                                                                }
                                                                ?>
                                                                <span class="badge bg-info"><?= h(implode(', ', $renderedValues)) ?></span>
                                                            <?php else : ?>
                                                                <span class="badge bg-secondary"><?= h($config['value']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($config['description'])) : ?>
                                                            <small class="text-muted d-block"><?= h($config['description']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>No configurations found.</strong>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'editConfigs', $sportConfigRef]) ?>" class="alert-link">
                                    Click here to add sport configurations
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Associated Teams Section -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Associated Teams</h4>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add', '?' => ['sport_id' => $sport->id]]) ?>"
                                class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle"></i> Add Team
                            </a>
                        </div>

                        <?php if (!empty($sport->teams)) : ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Team Name</th>
                                            <th>Abbreviation</th>
                                            <th>Gender</th>
                                            <th>Description</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sport->teams as $team) : ?>
                                            <tr>
                                                <td>
                                                    <strong><?= h($team->team_name) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= h($team->abbr) ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $genderLabels = [
                                                        'M' => ['Male', 'primary'],
                                                        'F' => ['Female', 'danger'],
                                                        'C' => ['Co-ed', 'success'],
                                                    ];
                                                    $genderInfo = $genderLabels[$team->gender] ?? ['Unknown', 'secondary'];
                                                    ?>
                                                    <span class="badge bg-<?= $genderInfo[1] ?>"><?= $genderInfo[0] ?></span>
                                                </td>
                                                <td>
                                                    <?= !empty($team->team_description) ? h($team->team_description) : '<em class="text-muted">No description</em>' ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>"
                                                            class="btn btn-outline-primary btn-sm"
                                                            title="View Team">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            title="Edit Team">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id]) ?>"
                                                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                                            data-item-type="team"
                                                            data-associated='<?= json_encode([['label' => $team->team_name, 'count' => 0, 'id' => $team->id]]) ?>'>
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="alert alert-info" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <span>No teams are currently associated with this sport.</span>
                                </div>
                                <div class="mt-2">
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add', '?' => ['sport_id' => $sport->id]]) ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-circle"></i> Add First Team
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Back to Sports List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'sport']) ?>
