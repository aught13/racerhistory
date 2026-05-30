<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $teamSeasonId
 * @var \App\Model\Entity\TeamSeason $teamSeason
 */

$ajaxUrl = $this->Url->build([
    'prefix' => 'Admin',
    'controller' => 'Games',
    'action' => 'ajaxList',
    '?' => $teamSeasonId ? ['team_season_id' => $teamSeasonId] : [],
]);

$bulkDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'bulk']);
?>
<?php $this->assign('title', 'Manage Games'); ?>
<div
    class="container py-4"
    data-controller="admin-games-index"
    data-admin-games-index-ajax-url-value="<?= h($ajaxUrl) ?>"
    data-admin-games-index-bulk-delete-url-value="<?= h($bulkDeleteUrl) ?>"
    data-admin-games-index-delete-form-id-value="delete-form-games-bulk"
>
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
            <form id="bulk-action-form-games" method="post" data-admin-games-index-target="bulkForm">
                <div class="mb-2 d-flex align-items-center gap-2" id="games-bulk-action-bar">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto" data-admin-games-index-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled data-admin-games-index-target="actionButton">Go</button>
                </div>

                <table class="table table-striped table-bordered" id="games-table" data-admin-games-index-target="table">
                    <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="select-all-games" aria-label="Select all games" data-admin-games-index-target="selectAll"></th>
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
            <?php $this->Form->unlockField('game_ids');
            $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('game_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'game']) ?>
