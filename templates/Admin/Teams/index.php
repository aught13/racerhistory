<?php $this->assign('title', 'Manage Teams'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Teams Management</h1>
            <p class="text-muted mb-3">
                Manage competitive teams for all sports. Teams represent individual units that compete within a specific sport category.
                Each team must be assigned to a sport and classified by gender (Male, Female, or Co-ed).
            </p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Team
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Teams</h2>
            <?php if (!$teams->isEmpty()) : ?>
            <form id="bulk-action-form" method="post">
                <div class="mb-2 d-flex align-items-center gap-2">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="teams-table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-teams"></th>
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
                            <td><input type="checkbox" name="team_ids[]" value="<?= $team->id ?>" class="team-checkbox">
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
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'view', $team->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
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
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add']) ?>"
                    class="btn btn-success">Add the first team</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllTeams = document.getElementById('select-all-teams');
        const teamCheckboxes = document.querySelectorAll('.team-checkbox');
        const bulkActionSelect = document.getElementById('bulk-action-select');
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        const bulkActionForm = document.getElementById('bulk-action-form');

        // Select all functionality
        if (selectAllTeams) {
            selectAllTeams.addEventListener('change', function() {
                teamCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionButton();
            });
        }

        // Individual checkbox change
        teamCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });

        // Bulk action selection change
        if (bulkActionSelect) {
            bulkActionSelect.addEventListener('change', updateBulkActionButton);
        }

        // Update bulk action button state
        function updateBulkActionButton() {
            const checkedBoxes = document.querySelectorAll('.team-checkbox:checked');
            const hasAction = bulkActionSelect.value !== '';
            bulkActionBtn.disabled = checkedBoxes.length === 0 || !hasAction;
        }

        // Handle bulk action form submission
        if (bulkActionForm) {
            bulkActionForm.addEventListener('submit', function(e) {
                const checkedBoxes = document.querySelectorAll('.team-checkbox:checked');
                const action = bulkActionSelect.value;

                if (checkedBoxes.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one team.');
                    return;
                }

                if (action === 'delete') {
                    e.preventDefault();
                    // Collect selected team names and ids to show in modal
                    var names = Array.from(checkedBoxes).map(cb => cb.closest('tr').querySelector('td:nth-child(2)').textContent.trim());
                    var ids = Array.from(checkedBoxes).map(cb => cb.value);
                    // Call the confirm helper directly to avoid relying on delegated click handling
                    window.showConfirmDelete && window.showConfirmDelete({
                        deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'bulkDelete']) ?>',
                        itemType: 'teams (bulk)',
                        associated: JSON.stringify(names),
                        ids: JSON.stringify(ids),
                        idsName: 'team_ids[]',
                        formId: 'delete-form-teams-bulk',
                        bulkAction: 'delete'
                    });
                }
            });
        }
    });
</script>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team']) ?>
