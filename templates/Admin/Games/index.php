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
            <?php if (!$games->isEmpty()) : ?>
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
                        <th>Opponent</th>
                        <th>Type</th>
                        <th>Place / Site</th>
                        <th>Score</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($games as $game) : ?>
                        <tr>
                            <td><input type="checkbox" name="game_ids[]" value="<?= $game->id ?>" class="game-checkbox" aria-label="Select game #<?= (int)$game->id ?>"></td>
                            <td><?= h($game->game_date) ?></td>
                            <td>
                                <?php if (isset($game->team_season->team) && isset($game->team_season->season)) : ?>
                                    <?= h($game->team_season->team->team_name) ?>
                                    <small class="text-muted">
                                        (<?= h($game->team_season->season->start . '-' . $game->team_season->season->end) ?>)
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($game->opponent->opponent_name ?? '-') ?></td>
                            <td><?= h($game->game_type->game_type_name ?? '-') ?></td>
                            <td>
                                <?= h($game->place->place_name ?? '-') ?><br>
                                <small class="text-muted"><?= h($game->site->site_name ?? '-') ?></small>
                            </td>
                            <td><?= h(($game->pts_mur ?? '') . ' - ' . ($game->pts_opp ?? '')) ?></td>
                            <td class="text-nowrap">
                                <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>" class="btn btn-sm btn-outline-secondary" aria-label="View game"><i class="bi bi-eye"></i></a>
                                <a href="<?= $this->Url->build(['action' => 'edit', $game->id]) ?>" class="btn btn-sm btn-primary" aria-label="Edit game"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk'], 'id' => 'delete-form-games-bulk', 'style' => 'display:none']) ?>
            <?php $this->Form->unlockField('game_ids'); $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('game_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
            <?php else : ?>
                <div class="alert alert-info" role="alert">No games have been created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'game']) ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all-games');
    const checkboxes = document.querySelectorAll('.game-checkbox');
    const actionSelect = document.getElementById('bulk-action-select');
    const actionBtn = document.getElementById('bulk-action-btn');

    function refreshState() {
        let checked = 0; checkboxes.forEach(cb => cb.checked && checked++);
        actionBtn.disabled = checked === 0 || !actionSelect.value;
    }
    selectAll && selectAll.addEventListener('change', () => { checkboxes.forEach(cb => (cb.checked = selectAll.checked)); refreshState(); });
    checkboxes.forEach(cb => cb.addEventListener('change', refreshState));
    actionSelect.addEventListener('change', refreshState);

    document.getElementById('bulk-action-form-games').addEventListener('submit', function (e) {
        e.preventDefault();
        if (actionSelect.value !== 'delete') return;
        const ids = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => cb.value);
        const assoc = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => {
            const row = cb.closest('tr');
            const date = row.querySelector('td:nth-child(2)').textContent.trim();
            const opp = row.querySelector('td:nth-child(4)').textContent.trim();
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
