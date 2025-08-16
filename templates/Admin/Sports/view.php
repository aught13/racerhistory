<?php $this->assign('title', 'View Sport'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?= h($sport->sport_name) ?> Details</h2>
                    <div class="btn-group" role="group">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>" class="btn btn-primary btn-sm">Edit</a>
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

                    <!-- Associated Teams Section -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Associated Teams</h4>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add', '?' => ['sport_id' => $sport->id]]) ?>"
                                class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle"></i> Add Team
                            </a>
                        </div>

                        <?php if (!empty($sport->teams)): ?>
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
                                        <?php foreach ($sport->teams as $team): ?>
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
                                                        'C' => ['Co-ed', 'success']
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
                        <?php else: ?>
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
