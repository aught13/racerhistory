<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\TeamSeason> $teamSeasons
 */
?>
<?php $this->assign('title', 'Manage Team Seasons'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Team Seasons Management</h1>
            <p class="text-muted mb-3">
                Manage team participation in specific seasons. Team seasons link teams to seasons and contain detailed
                competition information including league participation, tournament results, and season-specific data.
            </p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Team Season
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Team Seasons</h2>
            <?php if (!$teamSeasons->isEmpty()) : ?>
            <form id="bulk-action-form" method="post">
                <div class="mb-2 d-flex align-items-center gap-2" id="team-seasons-bulk-action-bar">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="team-seasons-table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-team-seasons"></th>
                            <th>Team</th>
                            <th>Season</th>
                            <th>Semester</th>
                            <th>League</th>
                            <th>League Finish</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamSeasons as $teamSeason) : ?>
                        <tr>
                            <td><input type="checkbox" name="team_season_ids[]" value="<?= $teamSeason->id ?>"
                                    class="team-season-checkbox">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <?= $this->element('team_season_image', ['teamSeason' => $teamSeason, 'size' => 'small']) ?>
                                    </div>
                                    <div>
                                    <?php if (isset($teamSeason->team)) : ?>
                                        <?= h($teamSeason->team->team_name) ?>
                                        <br><small class="text-muted"><?= h($teamSeason->team->abbr) ?></small>
                                    <?php else : ?>
                                        <em>Team not loaded</em>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (isset($teamSeason->season)) : ?>
                                    <?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?>
                                <?php else : ?>
                                    <em>Season not loaded</em>
                                <?php endif; ?>
                            </td>
                            <td><?= h($teamSeason->semester) ?></td>
                            <td>
                                <?= h($teamSeason->league ?: '-') ?>
                                <?php if ($teamSeason->league_abbr) : ?>
                                    <br><small class="text-muted"><?= h($teamSeason->league_abbr) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($teamSeason->league_finish ?: '-') ?></td>
                            <td>
                                <?php if ($teamSeason->created_at instanceof DateTimeInterface) : ?>
                                    <?= h($teamSeason->created_at->format('M j, Y')) ?>
                                <?php else : ?>
                                    <?= h($teamSeason->created_at) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'edit', $teamSeason->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                                    class="btn btn-sm btn-success">Add Roster</a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirm-delete-modal"
                                    data-delete-url="<?= $this->Url->build([
                                        'prefix' => 'Admin',
                                        'controller' => 'TeamSeasons',
                                        'action' => 'delete',
                                        $teamSeason->id,
                                    ]) ?>"
                                    data-item-type="team season"
                                    data-item-name="<?= h($teamSeason->team->team_name . ' (' . $teamSeason->season->start . '-' . $teamSeason->season->end . ')') ?>"
                                    aria-label="Delete team season for <?= h($teamSeason->team->team_name) ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'bulkDelete'], 'id' => 'delete-form-team-seasons-bulk', 'style' => 'display:none']) ?>
                <?php
                // Unlock dynamic fields for FormProtection
                $this->Form->unlockField('team_season_ids');
                $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('team_season_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
            <?php else : ?>
            <div class="alert alert-info">No team seasons have been created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#team-seasons-table').DataTable({
        "pagingType": "simple_numbers",
        "order": [[ 2, "desc" ]], // Sort by season descending
        "drawCallback": function(settings) {
            var api = this.api();
            var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
            if (api.page.info().pages <= 1) {
                pagination.hide();
            } else {
                pagination.show();
            }
        }
    });

    // Enable/disable bulk action button
    $(document).on('change', '.team-season-checkbox, #select-all-team-seasons, #bulk-action-select', function() {
        var checked = $('.team-season-checkbox:checked').length;
        var action = $('#bulk-action-select').val();
        $('#bulk-action-btn').prop('disabled', checked === 0 || !action);
    });

    // Select all checkboxes
    $('#select-all-team-seasons').on('change', function() {
        $('.team-season-checkbox').prop('checked', this.checked).trigger('change');
    });

    // Handle bulk action form submission -> open modal with selected item names
    $('#bulk-action-form').on('submit', function(e) {
        e.preventDefault();
        var action = $('#bulk-action-select').val();
        if (!action) return;
        if (action === 'delete') {
            var names = $('.team-season-checkbox:checked').map(function() {
                var teamName = $(this).closest('tr').find('td:nth-child(2)').text().trim().split('\n')[0];
                var seasonName = $(this).closest('tr').find('td:nth-child(3)').text().trim();
                return teamName + ' (' + seasonName + ')';
            }).get();
            var ids = $('.team-season-checkbox:checked').map(function() { return $(this).val(); }).get();
            window.showConfirmDelete && window.showConfirmDelete({
                deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'bulk']) ?>',
                itemType: 'team seasons (bulk)',
                associated: names,
                ids: ids,
                idsName: 'team_season_ids[]',
                formId: 'delete-form-team-seasons-bulk',
                bulkAction: 'delete'
            });
        }
    });
});
</script>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team season']) ?>
