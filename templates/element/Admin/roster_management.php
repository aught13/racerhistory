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
            <?php if (count($teamSeasonRosters) > 0) : ?>
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
        <?php if (count($teamSeasonRosters) > 0) : ?>
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

<script>
(function() {
    'use strict';

    function initRostersTable() {
        const table = document.getElementById('rosters-table');
        const $ = window.jQuery;
        if (!table || !$ || !$.fn || !$.fn.DataTable) {
            return;
        }

        if ($.fn.DataTable.isDataTable(table)) {
            return;
        }

        $(table).DataTable({
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
            drawCallback: function() {
                const api = this.api();
                const pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(api.page.info().pages > 1);
            }
        });
    }

    function initBulkActions() {
        const bulkForm = document.getElementById('bulk-action-form-rosters');
        if (!bulkForm || bulkForm.dataset.rhBulkBound === 'true') {
            return;
        }
        bulkForm.dataset.rhBulkBound = 'true';

        const selectAll = bulkForm.querySelector('#select-all-rosters');
        const actionSelect = bulkForm.querySelector('#bulk-action-select-rosters');
        const btn = bulkForm.querySelector('#bulk-action-btn-rosters');

        if (!actionSelect || !btn) {
            return;
        }

        function updateBulkActionButton() {
            const checked = bulkForm.querySelectorAll('.roster-checkbox:checked').length;
            btn.disabled = checked === 0 || !actionSelect.value;
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                bulkForm.querySelectorAll('.roster-checkbox').forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkActionButton();
            });
        }

        bulkForm.querySelectorAll('.roster-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });
        actionSelect.addEventListener('change', updateBulkActionButton);

        bulkForm.addEventListener('submit', function(event) {
            if (actionSelect.value !== 'delete') {
                return;
            }

            event.preventDefault();
            const ids = Array.from(
                bulkForm.querySelectorAll('.roster-checkbox:checked'),
            ).map((checkbox) => checkbox.value);

            if (window.__rhStimulusShowConfirmDelete) {
                window.__rhStimulusShowConfirmDelete({
                    deleteUrl: bulkForm.action,
                    itemType: 'roster entries (bulk)',
                    ids: JSON.stringify(ids),
                    idsName: 'team_season_roster_ids[]',
                    bulkAction: 'delete'
                });
            }
        });
    }

    function initRosterManagement() {
        initRostersTable();
        initBulkActions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRosterManagement, {
            once: true
        });
    } else {
        initRosterManagement();
    }

    document.addEventListener('turbo:load', initRosterManagement);
    document.addEventListener('turbo:frame-load', initRosterManagement);
})();
</script>
