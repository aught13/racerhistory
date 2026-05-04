<?php
/**
 * Roster management partial for Team Seasons view.
 *
 * This element displays a table of team season rosters with bulk action
 * capabilities and DataTables integration. It is intended to be included
 * in the `view.php` template of the `TeamSeasons` controller.
 *
 * Variables expected:
 * - `teamSeasonRosters`: A collection of `TeamSeasonRoster` entities.
 * - `teamSeason`: The `TeamSeason` entity being viewed.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRosters
 * @var \App\Model\Entity\TeamSeason $teamSeason
 */
?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Team Roster</h3>
        <div class="d-flex gap-2">
            <?php if (!$teamSeasonRosters->isEmpty()) : ?>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'bulkEdit', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Edit All
            </a>
            <?php endif; ?>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Add Roster Entry
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!$teamSeasonRosters->isEmpty()) : ?>
        <form id="bulk-action-form-rosters"
            action="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'bulk']) ?>"
            method="post">
            <div class="mb-2 d-flex align-items-center gap-2" id="rosters-bulk-action-bar">
                <label for="bulk-action-select-rosters" class="form-label mb-0">With Selected:</label>
                <select id="bulk-action-select-rosters" name="bulk_action" class="form-select form-select-sm w-auto">
                    <option value="">Choose...</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn-rosters" disabled>Go</button>
            </div>

            <table class="table table-striped table-bordered" id="rosters-table">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="select-all-rosters"></th>
                        <th>Person</th>
                        <th>Year</th>
                        <th>Number</th>
                        <th>Position</th>
                        <th>Height</th>
                        <th>Weight</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamSeasonRosters as $roster) : ?>
                    <tr>
                        <td><input type="checkbox" name="team_season_roster_ids[]" value="<?= $roster->id ?>"
                                class="roster-checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?= $this->element('person_image', ['person' => $roster->person, 'size' => 'small', 'class' => 'me-2']) ?>
                                <a
                                    href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'view', $roster->person->id]) ?>">
                                    <?= h($roster->person->display ?? ($roster->person->first . ' ' . $roster->person->last)) ?>
                                </a>
                            </div>
                        </td>
                        <td><?= h($roster->roster_year) ?></td>
                        <td><?= h($roster->roster_number) ?></td>
                        <td><?= h($roster->roster_position) ?></td>
                        <td><?= h($roster->roster_height) ?></td>
                        <td><?= h($roster->roster_weight) ?></td>
                        <td>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'edit', $roster->id]) ?>"
                                class="btn btn-sm btn-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                data-bs-target="#confirm-delete-modal"
                                data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'delete', $roster->id]) ?>"
                                data-item-type="roster entry"
                                data-item-name="<?= h($roster->person->display ?? 'this roster entry') ?>"
                                aria-label="Delete roster entry for <?= h($roster->person->display) ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php else : ?>
        <div class="alert alert-info">
            No roster entries have been created for this team season yet.
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                class="alert-link">Add the first roster entry</a>.
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal']) ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    if (window.jQuery && $('#rosters-table').length) {
        $('#rosters-table').DataTable({
            pagingType: 'simple_numbers',
            order: [
                [3, 'asc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [0, 7]
            }],
            language: {
                search: 'Search roster:'
            },
            drawCallback: function(settings) {
                const api = this.api();
                const pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(api.page.info().pages > 1);
            }
        });
    }

    const selectAll = document.getElementById('select-all-rosters');
    const checkboxes = document.querySelectorAll('.roster-checkbox');
    const actionSelect = document.getElementById('bulk-action-select-rosters');
    const btn = document.getElementById('bulk-action-btn-rosters');

    function updateBulkActionButton() {
        const checked = document.querySelectorAll('.roster-checkbox:checked').length;
        btn.disabled = checked === 0 || !actionSelect.value;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkActionButton();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkActionButton));
    if (actionSelect) {
        actionSelect.addEventListener('change', updateBulkActionButton);
    }

    const bulkForm = document.getElementById('bulk-action-form-rosters');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            if (actionSelect.value === 'delete') {
                e.preventDefault();
                const ids = Array.from(document.querySelectorAll('.roster-checkbox:checked')).map(cb => cb
                    .value);
                window.showConfirmDelete({
                    deleteUrl: this.action,
                    itemType: 'roster entries (bulk)',
                    ids: JSON.stringify(ids),
                    idsName: 'team_season_roster_ids[]',
                    bulkAction: 'delete'
                });
            }
        });
    }
});
</script>
