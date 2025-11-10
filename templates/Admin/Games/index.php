<?php $this->assign('title', 'Manage Games'); ?>
<div class="container py-4">
    <?php if (isset($teamSeason)) : ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">
                        Team Seasons
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>">
                        <?= h($teamSeason->team->team_name) ?> (<?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?>)
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Games</li>
            </ol>
        </nav>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col">
            <?php if (isset($teamSeason)) : ?>
                <h1 class="mb-3">
                    Games for <?= h($teamSeason->team->team_name) ?>
                    <small class="text-muted"><?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?></small>
                </h1>
            <?php else : ?>
                <h1 class="mb-3">Games Management</h1>
            <?php endif; ?>

            <div class="d-flex gap-2 mb-3">
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'add'] + (isset($teamSeason) ? ['?' => ['team_season_id' => $teamSeason->id]] : [])) ?>"
                   class="btn btn-success" aria-label="Add new game">
                    <i class="bi bi-plus-circle"></i> Add New Game
                </a>
                <?php if (isset($teamSeason)) : ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Team Season
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <form id="bulk-action-form-games" method="post">
                <div class="mb-2 d-flex align-items-center gap-2" id="games-bulk-action-bar">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="games-table">
                    <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="select-all-games" aria-label="Select all games"></th>
                        <th>Date</th>
                        <th>Team Season</th>
                        <th>H/R/N</th>
                        <th>Opponent</th>
                        <th>Type</th>
                        <th>Place</th>
                        <th>Score</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </form>

            <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk'], 'id' => 'delete-form-games-bulk', 'style' => 'display:none']) ?>
            <?php $this->Form->unlockField('game_ids'); $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('game_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'game']) ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/dataTables.searchBuilder.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/searchBuilder.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTables with server-side processing
    const teamSeasonId = <?= json_encode($teamSeasonId ?? null) ?>;
    const ajaxUrl = <?= json_encode($this->Url->build([
        'prefix' => 'Admin',
        'controller' => 'Games',
        'action' => 'ajaxList',
        '?' => $teamSeasonId ? ['team_season_id' => $teamSeasonId] : []
    ])) ?>;

    const table = $('#games-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'GET'
        },
        columns: [
            { data: 'checkbox', name: 'checkbox', title: '', orderable: false, searchable: false },
            { data: 'game_date', name: 'game_date', title: 'Date', type: 'date' },
            { data: 'team_season', name: 'team_season', title: 'Team Season', type: 'string' },
            { data: 'hrn', name: 'hrn', title: 'H/R/N', type: 'string' },
            { data: 'opponent', name: 'opponent', title: 'Opponent', type: 'string' },
            { data: 'game_type', name: 'game_type', title: 'Type', type: 'string' },
            { data: 'place', name: 'place', title: 'Place', type: 'string' },
            { data: 'score', name: 'score', title: 'Score', orderable: false, searchable: false },
            // Hidden columns for SearchBuilder
            { data: 'place_state', name: 'place_state', title: 'State', type: 'string', visible: false },
            { data: 'mur_pts', name: 'mur_pts', title: 'Team Points', type: 'num', visible: false },
            { data: 'opp_pts', name: 'opp_pts', title: 'Opponent Points', type: 'num', visible: false },
            { data: 'mur_rk', name: 'mur_rk', title: 'Team Rank', type: 'num', visible: false },
            { data: 'opp_rk', name: 'opp_rk', title: 'Opponent Rank', type: 'num', visible: false },
            { data: 'result', name: 'result', title: 'Result (W/L/T)', type: 'string', visible: false },
            { data: 'conf', name: 'conf', title: 'Conference Game', type: 'num', visible: false },
            { data: 'post', name: 'post', title: 'Postseason', type: 'num', visible: false }
        ],
        order: [[1, 'desc']], // Sort by date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            processing: 'Loading games...'
        },
        dom: 'Qlfrtip', // Q = SearchBuilder
        searchBuilder: {
            columns: [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15], // Enable on visible + hidden searchable columns
            depthLimit: 2
        }
    });

    const selectAll = document.getElementById('select-all-games');
    const actionSelect = document.getElementById('bulk-action-select');
    const actionBtn = document.getElementById('bulk-action-btn');

    function refreshState() {
        const checkboxes = document.querySelectorAll('.game-checkbox');
        let checked = 0;
        checkboxes.forEach(cb => cb.checked && checked++);
        actionBtn.disabled = checked === 0 || !actionSelect.value;
    }

    selectAll && selectAll.addEventListener('change', () => {
        const checkboxes = document.querySelectorAll('.game-checkbox');
        checkboxes.forEach(cb => (cb.checked = selectAll.checked));
        refreshState();
    });

    // Use event delegation for dynamically rendered checkboxes
    document.getElementById('games-table').addEventListener('change', function(e) {
        if (e.target.classList.contains('game-checkbox')) {
            refreshState();
        }
    });

    actionSelect.addEventListener('change', refreshState);

    document.getElementById('bulk-action-form-games').addEventListener('submit', function (e) {
        e.preventDefault();
        if (actionSelect.value !== 'delete') return;
        const ids = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => cb.value);
        const assoc = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => {
            const row = cb.closest('tr');
            const date = row.querySelector('td:nth-child(2)').textContent.trim();
            const opp = row.querySelector('td:nth-child(5)').textContent.trim();
            return date + ' vs ' + opp;
        });
        window.showConfirmDelete && window.showConfirmDelete({
            deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk']) ?>',
            itemType: 'games (bulk)',
            associated: assoc,
            ids: ids,
            idsName: 'game_ids[]',
            formId: 'delete-form-games-bulk',
            bulkAction: 'delete'
        });
    });
});
</script>
