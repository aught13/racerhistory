<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Sport> $sports
 */
?>
<?php $this->assign('title', 'Manage Sports'); ?>
<div
    class="container py-4"
    data-controller="admin-bulk-table"
    data-admin-bulk-table-bulk-delete-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'bulkDelete'])) ?>"
    data-admin-bulk-table-item-type-value="sports (bulk)"
    data-admin-bulk-table-ids-name-value="sport_ids[]"
    data-admin-bulk-table-form-id-value="delete-form-sports-bulk"
    data-admin-bulk-table-name-column-value="2"
>
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Sports Management</h1>
            <p class="text-muted mb-3">
                Manage sport categories that teams compete in. Sports are the foundation of the system - each team must
                be assigned to a sport.
                Sport names must be unique across the system.
            </p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Sport
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Sports</h2>
            <?php if (!$sports->isEmpty()) : ?>
            <form id="bulk-action-form" method="post" data-admin-bulk-table-target="bulkForm">
                <div class="mb-2 d-flex align-items-center gap-2" id="sports-bulk-action-bar">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto" data-admin-bulk-table-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled data-admin-bulk-table-target="actionButton">Go</button>
                </div>

                <table class="table table-striped table-bordered" id="sports-table" data-admin-bulk-table-target="table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-sports" data-admin-bulk-table-target="selectAll"></th>
                            <th>Sport Name <small class="text-light">(Unique, 162 chars max)</small></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sports as $sport) : ?>
                        <tr>
                                <td><input type="checkbox" name="sport_ids[]" value="<?= $sport->id ?>" data-admin-bulk-table-role="row-checkbox"
                                    class="sport-checkbox">
                            </td>
                            <td><?= h($sport->sport_name) ?></td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $sport->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'configs', $sport->id]) ?>"
                                    class="btn btn-sm btn-warning" title="Configure period names, officials, and settings">
                                    <i class="fas fa-cog"></i> Config
                                </a>
                                <?php
                                    $teamCount = isset($sport->teams) ? count($sport->teams) : 0;
                                    $associated = json_encode([
                                        ['label' => 'Teams', 'count' => $teamCount],
                                    ]);
                                ?>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                    data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id]) ?>"
                                    data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                                    data-item-type="sport" data-associated='<?= $associated ?>'
                                    data-form-id="<?= 'delete-form-sport-' . $sport->id ?>" aria-label="Delete sport <?= h($sport->sport_name) ?>">Delete</button>
                                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id], 'id' => 'delete-form-sport-' . $sport->id, 'style' => 'display:none']) ?>
                                <?= $this->Form->end() ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'bulkDelete'], 'id' => 'delete-form-sports-bulk', 'style' => 'display:none']) ?>
                <?php
                // Unlock dynamic fields for FormProtection
                $this->Form->unlockField('sport_ids');
                $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('sport_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
            <?php else : ?>
            <div class=" alert alert-info">No sports have been created yet.
        </div>
            <?php endif; ?>
    </div>
</div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'sport']) ?>
