<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Season> $seasons
 */

$canCreateSeasons = $this->Rbac->can('Seasons', 'create');
$canReadSeasons = $this->Rbac->can('Seasons', 'read');
$canUpdateSeasons = $this->Rbac->can('Seasons', 'update');
$canDeleteSeasons = $this->Rbac->can('Seasons', 'delete');
?>
<?php $this->assign('title', 'Manage Seasons'); ?>
<div
    class="container py-4"
    data-controller="admin-bulk-table"
    data-admin-bulk-table-bulk-delete-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'bulkDelete'])) ?>"
    data-admin-bulk-table-item-type-value="seasons (bulk)"
    data-admin-bulk-table-ids-name-value="season_ids[]"
    data-admin-bulk-table-form-id-value="delete-form-seasons-bulk"
    data-admin-bulk-table-name-column-value="4"
    data-admin-bulk-table-order-column-value="1"
    data-admin-bulk-table-order-direction-value="desc"
>
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Seasons Management</h1>
            <p class="text-muted mb-3">
                Manage season periods that organize team competitions by time. Seasons define the academic or calendar
                year structure for sports activities. Each season can have multiple teams competing in it.
            </p>
            <?php if ($canCreateSeasons) : ?>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'add']) ?>"
                    class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Season
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Seasons</h2>
            <?php if (!$seasons->isEmpty()) : ?>
            <form id="bulk-action-form" method="post" data-admin-bulk-table-target="bulkForm">
                <div class="mb-2 d-flex align-items-center gap-2" id="seasons-bulk-action-bar">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto" data-admin-bulk-table-target="actionSelect">
                        <option value="">Choose...</option>
                        <?php if ($canDeleteSeasons) : ?>
                            <option value="delete">Delete</option>
                        <?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled data-admin-bulk-table-target="actionButton"<?= $canDeleteSeasons ? '' : ' aria-disabled="true"' ?>>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="seasons-table" data-admin-bulk-table-target="table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-seasons" data-admin-bulk-table-target="selectAll"<?= $canDeleteSeasons ? '' : ' disabled' ?>></th>
                            <th>Start Year</th>
                            <th>End Year</th>
                            <th>Season Display</th>
                            <th>Team Seasons</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seasons as $season) : ?>
                        <tr>
                                <td><input type="checkbox" name="season_ids[]" value="<?= $season->id ?>" data-admin-bulk-table-role="row-checkbox"
                                    class="season-checkbox"<?= $canDeleteSeasons ? '' : ' disabled' ?>>
                            </td>
                            <td><?= h($season->start) ?></td>
                            <td><?= h($season->end) ?></td>
                            <td><?= h($season->start . '-' . $season->end) ?></td>
                            <td>
                                <?php $teamSeasonCount = isset($season->team_seasons) ? count($season->team_seasons) : 0; ?>
                                <span class="badge bg-secondary"><?= $teamSeasonCount ?></span>
                            </td>
                            <td>
                                <?php if ($season->created_at instanceof DateTimeInterface) : ?>
                                    <?= h($season->created_at->format('M j, Y g:i A')) ?>
                                <?php else : ?>
                                    <?= h($season->created_at) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canReadSeasons) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'view', $season->id]) ?>"
                                        class="btn btn-sm btn-info">View</a>
                                <?php endif; ?>
                                <?php if ($canUpdateSeasons) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'edit', $season->id]) ?>"
                                        class="btn btn-sm btn-primary">Edit</a>
                                <?php endif; ?>
                                <?php
                                    $teamSeasonCount = isset($season->team_seasons) ? count($season->team_seasons) : 0;
                                    $associated = json_encode([
                                        ['label' => 'Team Seasons', 'count' => $teamSeasonCount],
                                    ]);
                                ?>
                                <?php if ($canDeleteSeasons) : ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                        data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'delete', $season->id]) ?>"
                                        data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'edit', $season->id]) ?>"
                                        data-item-type="season" data-associated='<?= $associated ?>'
                                        data-form-id="<?= 'delete-form-season-' . $season->id ?>" aria-label="Delete season <?= h($season->start . '-' . $season->end) ?>">Delete</button>
                                    <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'delete', $season->id], 'id' => 'delete-form-season-' . $season->id, 'style' => 'display:none']) ?>
                                    <?= $this->Form->end() ?>
                                <?php endif; ?>
                                <?php if (!$canReadSeasons && !$canUpdateSeasons && !$canDeleteSeasons) : ?>
                                    <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'bulkDelete'], 'id' => 'delete-form-seasons-bulk', 'style' => 'display:none']) ?>
                <?php
                // Unlock dynamic fields for FormProtection
                $this->Form->unlockField('season_ids');
                $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('season_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
            <?php else : ?>
            <div class="alert alert-info">
                No seasons have been created yet.
                <?php if ($canCreateSeasons) : ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'add']) ?>" class="alert-link">Add the first season</a>.
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'season']) ?>
