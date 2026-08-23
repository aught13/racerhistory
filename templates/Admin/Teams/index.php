<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Team> $teams
 */

$canCreateTeams = $this->Rbac->can('Teams', 'create');
$canReadTeams = $this->Rbac->can('Teams', 'read');
$canUpdateTeams = $this->Rbac->can('Teams', 'update');
$canDeleteTeams = $this->Rbac->can('Teams', 'delete');
?>
<?php $this->assign('title', 'Manage Teams'); ?>
<div
    class="container py-4"
    data-controller="admin-bulk-table"
    data-admin-bulk-table-bulk-delete-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'bulkDelete'])) ?>"
    data-admin-bulk-table-item-type-value="teams (bulk)"
    data-admin-bulk-table-ids-name-value="team_ids[]"
    data-admin-bulk-table-form-id-value="delete-form-teams-bulk"
    data-admin-bulk-table-name-column-value="2"
    data-admin-bulk-table-order-column-value="1"
>
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Teams Management</h1>
            <p class="text-muted mb-3">
                Manage competitive teams for all sports. Teams represent individual units that compete within a specific sport category.
                Each team must be assigned to a sport and classified by gender (Male, Female, or Co-ed).
            </p>
            <?php if ($canCreateTeams) : ?>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add']) ?>"
                    class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Team
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Teams</h2>
            <?php if (!$teams->isEmpty()) : ?>
            <form id="bulk-action-form" method="post" data-admin-bulk-table-target="bulkForm">
                <div class="mb-2 d-flex align-items-center gap-2">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto" data-admin-bulk-table-target="actionSelect">
                        <option value="">Choose...</option>
                        <?php if ($canDeleteTeams) : ?>
                            <option value="delete">Delete</option>
                        <?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" <?= $canDeleteTeams ? 'disabled' : 'disabled aria-disabled="true"' ?> data-admin-bulk-table-target="actionButton">Go</button>
                </div>

                <table class="table table-striped table-bordered" id="teams-table" data-admin-bulk-table-target="table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-teams" data-admin-bulk-table-target="selectAll"<?= $canDeleteTeams ? '' : ' disabled' ?>></th>
                            <th>Team Name <small class="text-light">(Short Name)</small></th>
                            <th>Sport <small class="text-light">(Category)</small></th>
                            <th>Abbreviation <small class="text-light">(5 chars max)</small></th>
                            <th>Gender <small class="text-light">(M/F/Co-ed)</small></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team) : ?>
                        <tr>
                            <td><input type="checkbox" name="team_ids[]" value="<?= $team->id ?>" class="team-checkbox" data-admin-bulk-table-role="row-checkbox"<?= $canDeleteTeams ? '' : ' disabled' ?>>
                            </td>
                            <td><?= h($team->team_name) ?></td>
                            <td><?= h($team->sport ? $team->sport->sport_name : 'N/A') ?></td>
                            <td><?= h($team->abbr) ?></td>
                            <td>
                                <?php
                                $genderLabels = ['M' => 'Male', 'F' => 'Female', 'C' => 'Co-ed'];
                                echo h($genderLabels[$team->gender] ?? $team->gender);
                                ?>
                            </td>
                            <td>
                                <?php if ($canReadTeams) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>"
                                        class="btn btn-sm btn-info">View</a>
                                <?php endif; ?>
                                <?php if ($canUpdateTeams) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                        class="btn btn-sm btn-primary">Edit</a>
                                <?php endif; ?>
                                <?php if ($canDeleteTeams) : ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                        data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id]) ?>"
                                        data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                        data-item-type="team"
                                        data-associated='<?= json_encode([$team->team_name, $team->abbr]) ?>'
                                        data-form-id="delete-form-team-<?= $team->id ?>">
                                        Delete
                                    </button>
                                    <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id], 'id' => 'delete-form-team-' . $team->id, 'style' => 'display:none']) ?>
                                    <?= $this->Form->end() ?>
                                <?php endif; ?>
                                <?php if (!$canReadTeams && !$canUpdateTeams && !$canDeleteTeams) : ?>
                                    <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'bulkDelete'], 'id' => 'delete-form-teams-bulk', 'style' => 'display:none']) ?>
                <?php
                // These fields will be injected client-side; unlock them so FormProtection allows their values to change.
                $this->Form->unlockField('team_ids');
                $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('team_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
            <?php else : ?>
            <div class="alert alert-info">
                <p>No teams found.</p>
                <?php if ($canCreateTeams) : ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add']) ?>"
                        class="btn btn-success">Add the first team</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team']) ?>
