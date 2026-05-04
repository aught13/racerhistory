<?php
/**
 * Delete confirmation page for a player game stat.
 *
 * Shows the stat details and prompts the user to confirm deletion.
 * Optionally deducts the stat from the player's season totals.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatBasketGamePerson $stat
 * @var \App\Model\Entity\Game $game
 */
$this->assign('title', 'Delete Player Stat');
$person = $stat->team_season_roster->person ?? null;
$playerName = $person ? ($person->display ?? $person->full ?? 'Unknown') : 'Unknown';
$rosterNumber = $stat->team_season_roster->roster_number ?? '';
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
                <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>">Player Stats</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Delete</li>
        </ol>
    </nav>

    <h1 class="mb-3">Delete Player Stat</h1>

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

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <?= $rosterNumber ? h("#{$rosterNumber} ") : '' ?><?= h($playerName) ?>
                <span class="badge bg-secondary ms-2"><?= h($stat->period ?? 'Z') ?></span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-auto"><strong>MIN:</strong> <?= h($stat->MIN ?? '—') ?></div>
                <div class="col-auto"><strong>FGM-FGA:</strong> <?= h($stat->FGM ?? '0') ?>-<?= h($stat->FGA ?? '0') ?></div>
                <div class="col-auto"><strong>3PM-3PA:</strong> <?= h($stat->TPM ?? '0') ?>-<?= h($stat->TPA ?? '0') ?></div>
                <div class="col-auto"><strong>FTM-FTA:</strong> <?= h($stat->FTM ?? '0') ?>-<?= h($stat->FTA ?? '0') ?></div>
                <div class="col-auto"><strong>RB:</strong> <?= h($stat->RB ?? '0') ?></div>
                <div class="col-auto"><strong>AST:</strong> <?= h($stat->AST ?? '0') ?></div>
                <div class="col-auto"><strong>STL:</strong> <?= h($stat->STL ?? '0') ?></div>
                <div class="col-auto"><strong>BS:</strong> <?= h($stat->BS ?? '0') ?></div>
                <div class="col-auto"><strong>TRN:</strong> <?= h($stat->TRN ?? '0') ?></div>
                <div class="col-auto"><strong>PTS:</strong> <span class="fw-bold text-primary"><?= h($stat->PTS ?? '0') ?></span></div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Are you sure you want to delete this stat entry? This action cannot be undone.
    </div>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'delete', $stat->id],
        'id' => 'delete-stat-form',
    ]) ?>

    <?php if ((string)($stat->period ?? '') === 'Z') : ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">Season Totals</h5>
            <div class="form-check">
                <input type="checkbox" name="deduct_from_totals" value="1"
                       class="form-check-input" id="deduct-from-totals-checkbox">
                <label class="form-check-label" for="deduct-from-totals-checkbox">
                    Deduct from season totals
                </label>
                <br>
                <small class="form-text text-muted">
                    Check this box to subtract these stats from the player's season totals.
                    Only available for final (period 'Z') stats.
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <?= $this->Form->button(__('Yes, Delete'), [
            'class' => 'btn btn-danger',
            'id' => 'confirm-delete-btn',
        ]) ?>
        <a href="<?= $this->Url->build(['action' => 'view', $game->id]) ?>"
           class="btn btn-secondary">Cancel</a>
    </div>

    <?= $this->Form->end() ?>
</div>
