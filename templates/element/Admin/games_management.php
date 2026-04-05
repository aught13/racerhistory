<?php
/**
 * Games Management Element for Team Season View
 *
 * Shows a list of games for the team season with add/edit/delete capabilities.
 * Similar to roster_management element.
 *
 * @var \App\Model\Entity\TeamSeason $teamSeason
 * @var \Cake\Collection\CollectionInterface|\App\Model\Entity\Game[] $teamSeasonGames
 */
?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Games for this Season</h3>
        <div class="btn-group">
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
               class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Add Game
            </a>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'index', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list"></i> Manage All Games
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!$teamSeasonGames->isEmpty()) : ?>
            <form id="bulk-action-form-games" method="post"
                  action="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk']) ?>">
                <div class="mb-2 d-flex align-items-center gap-2" id="games-bulk-action-bar">
                    <label for="bulk-action-select-games" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select-games" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn-games" disabled>Go</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="games-table">
                        <thead class="table-dark">
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="select-all-games" aria-label="Select all games">
                            </th>
                            <th>Date</th>
                            <th>Opponent</th>
                            <th>Type</th>
                            <th>Place / Site</th>
                            <th>Score</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($teamSeasonGames as $game) : ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="game_ids[]" value="<?= $game->id ?>"
                                           class="game-checkbox" aria-label="Select game">
                                </td>
                                <td>
                                    <?php if ($game->game_date instanceof \DateTimeInterface) : ?>
                                        <?= h($game->game_date->format('M j, Y')) ?>
                                    <?php else : ?>
                                        <?= h($game->game_date) ?>
                                    <?php endif; ?>
                                    <?php if ($game->game_time) : ?>
                                        <br><small class="text-muted"><?= h($game->game_time) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($game->opponent->opponent_name ?? 'TBD') ?>
                                    <?php if (isset($game->opponent->place->place_city)) : ?>
                                        <br><small class="text-muted"><?= h($game->opponent->place->place_city) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($game->game_type->game_type_name ?? '-') ?></td>
                                <td>
                                    <?= h($game->place->place_city ?? '-') ?>
                                    <?php if (isset($game->site->site_name)) : ?>
                                        <br><small class="text-muted"><?= h($game->site->site_name) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($game->pts_mur || $game->pts_opp) : ?>
                                        <span class="<?= ($game->pts_mur > $game->pts_opp) ? 'text-success fw-bold' : (($game->pts_mur < $game->pts_opp) ? 'text-danger' : '') ?>">
                                            <?= h(($game->pts_mur ?? '') . ' - ' . ($game->pts_opp ?? '')) ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'view', $game->id]) ?>"
                                           class="btn btn-outline-secondary" aria-label="View game" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'edit', $game->id]) ?>"
                                           class="btn btn-primary" aria-label="Edit game" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?= $this->Form->create(null, [
                'url' => ['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk'],
                'id' => 'delete-form-games-bulk',
                'style' => 'display:none'
            ]) ?>
            <?php
            $this->Form->unlockField('game_ids');
            $this->Form->unlockField('bulk_action');
            ?>
            <?= $this->Form->hidden('game_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>

        <?php else : ?>
            <div class="alert alert-info mb-0" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        No games have been created for this team season yet.
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                           class="alert-link">Add the first game</a>.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal-games']) ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Games bulk selection functionality
    const selectAllGames = document.getElementById('select-all-games');
    const gameCheckboxes = document.querySelectorAll('.game-checkbox');
    const actionSelectGames = document.getElementById('bulk-action-select-games');
    const actionBtnGames = document.getElementById('bulk-action-btn-games');

    function refreshGamesState() {
        let checked = 0;
        gameCheckboxes.forEach(cb => cb.checked && checked++);
        if (actionBtnGames) {
            actionBtnGames.disabled = checked === 0 || !actionSelectGames.value;
        }
    }

    if (selectAllGames) {
        selectAllGames.addEventListener('change', () => {
            gameCheckboxes.forEach(cb => (cb.checked = selectAllGames.checked));
            refreshGamesState();
        });
    }

    gameCheckboxes.forEach(cb => cb.addEventListener('change', refreshGamesState));
    if (actionSelectGames) {
        actionSelectGames.addEventListener('change', refreshGamesState);
    }

    // Handle bulk delete confirmation
    const bulkFormGames = document.getElementById('bulk-action-form-games');
    if (bulkFormGames) {
        bulkFormGames.addEventListener('submit', function (e) {
            e.preventDefault();
            if (actionSelectGames.value !== 'delete') return;

            const ids = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => cb.value);
            const assoc = Array.from(document.querySelectorAll('.game-checkbox:checked')).map(cb => {
                const row = cb.closest('tr');
                const date = row.querySelector('td:nth-child(2)').textContent.trim().split('\n')[0];
                const opp = row.querySelector('td:nth-child(3)').textContent.trim().split('\n')[0];
                return date + ' vs ' + opp;
            });

            if (window.showConfirmDelete) {
                window.showConfirmDelete({
                    deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk']) ?>',
                    itemType: 'games (bulk)',
                    associated: assoc,
                    ids: ids,
                    idsName: 'game_ids[]',
                    formId: 'delete-form-games-bulk',
                    bulkAction: 'delete'
                });
            }
        });
    }
});
</script>
