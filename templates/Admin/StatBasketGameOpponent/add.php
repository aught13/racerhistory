<?php
/**
 * Multi-row opponent stat add form.
 *
 * Allows adding multiple opponent player stat entries at once for a game.
 * Users can click "+" to add rows and "Save All" to commit them
 * in a single POST via bulkAdd.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Game $game
 */
$this->assign('title', 'Add Opponent Player');
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">
                    Game Details
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>">Opponent Stats</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Add</li>
        </ol>
    </nav>

    <h1 class="mb-3">Add Opponent Player</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <?= h($game->team_season->team->team_name ?? 'Team') ?> vs
                <?= h($game->opponent->opponent_name ?? 'Opponent') ?>
                <span class="text-muted">
                    (<?= $game->game_date ? $game->game_date->format('M j, Y') : '' ?>)
                </span>
            </h5>
        </div>
    </div>

    <turbo-frame id="stat-opponent-add-frame">
    <?= $this->Form->create(null, [
        'id' => 'bulk-stat-opponent-form',
        'url' => ['action' => 'bulkAdd', $game->id],
    ]) ?>

    <div id="stat-rows" data-stat-type="opponent">
        <!-- Initial row rendered server-side -->
        <div class="card mb-3 stat-row" data-row-index="0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="stat-row-label">Player #1</span>
                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row" disabled>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="rows[0][name]" class="form-control stat-opp-name" placeholder="e.g., John Smith" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Jersey</label>
                        <input type="text" name="rows[0][jersey]" class="form-control" placeholder="23">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Pos</label>
                        <input type="text" name="rows[0][position]" class="form-control" placeholder="G">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Period</label>
                        <input type="text" name="rows[0][period]" class="form-control" placeholder="Z" value="Z">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">GS</label>
                        <div class="form-check mt-1">
                            <input type="checkbox" name="rows[0][GS]" value="1" class="form-check-input">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">MIN</label>
                        <input type="text" name="rows[0][MIN]" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-1">
                        <label class="form-label">FGM</label>
                        <input type="text" name="rows[0][FGM]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">FGA</label>
                        <input type="text" name="rows[0][FGA]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">3PM</label>
                        <input type="text" name="rows[0][TPM]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">3PA</label>
                        <input type="text" name="rows[0][TPA]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">FTM</label>
                        <input type="text" name="rows[0][FTM]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">FTA</label>
                        <input type="text" name="rows[0][FTA]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">ORB</label>
                        <input type="text" name="rows[0][ORB]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">DRB</label>
                        <input type="text" name="rows[0][DRB]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">RB</label>
                        <input type="text" name="rows[0][RB]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">AST</label>
                        <input type="text" name="rows[0][AST]" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-1">
                        <label class="form-label">STL</label>
                        <input type="text" name="rows[0][STL]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">BS</label>
                        <input type="text" name="rows[0][BS]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">BD</label>
                        <input type="text" name="rows[0][BD]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">TRN</label>
                        <input type="text" name="rows[0][TRN]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">PF</label>
                        <input type="text" name="rows[0][PF]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">TF</label>
                        <input type="text" name="rows[0][TF]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">FD</label>
                        <input type="text" name="rows[0][FD]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">PTS *</label>
                        <input type="text" name="rows[0][PTS]" class="form-control" required>
                    </div>
                </div>
                <input type="hidden" name="rows[0][GP]" value="1">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
        <button type="button" id="add-row-btn" class="btn btn-outline-success btn-sm">
            <i class="bi bi-plus-circle"></i> Add Another
        </button>
    </div>

    <div class="d-flex gap-2">
        <?= $this->Form->button(__('Save All'), [
            'class' => 'btn btn-primary',
            'id' => 'save-all-btn',
        ]) ?>
        <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>" class="btn btn-secondary">Cancel</a>
    </div>

    <?= $this->Form->end() ?>
    </turbo-frame>
</div>

<?php $this->append('script'); ?>
<script type="module">
import { initStatMultiAdd } from '/js/modules/stat-multi-add.mjs';

function boot() {
    initStatMultiAdd();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
document.addEventListener('turbo:load', boot);
</script>
<?php $this->end(); ?>
