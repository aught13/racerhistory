<?php $this->assign('title', 'Manage Teams'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Teams Management</h1>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">Add New Team</a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Teams</h2>
            <?php if (!$teams->isEmpty()): ?>
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
                            <th>Team Name</th>
                            <th>Sport</th>
                            <th>Abbreviation</th>
                            <th>Gender</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
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
                                <?= $this->Form->postLink('Delete',
                                    ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id],
                                    ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Are you sure you want to delete this team?']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?php else: ?>
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
                    if (confirm(`Are you sure you want to delete ${checkedBoxes.length} team(s)?`)) {
                        // Update form action and submit
                        this.action = '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'bulk']) ?>';
                        
                        // Add bulk_action field
                        const bulkActionInput = document.createElement('input');
                        bulkActionInput.type = 'hidden';
                        bulkActionInput.name = 'bulk_action';
                        bulkActionInput.value = 'delete';
                        this.appendChild(bulkActionInput);
                        
                        this.submit();
                    }
                }
            });
        }
    });
</script>
