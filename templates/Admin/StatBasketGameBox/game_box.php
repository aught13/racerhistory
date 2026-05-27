<?php
/**
 * Game Box Score Entry Template
 * For entering team and opponent final (period Z) box scores
 *
 * @var \App\View\AppView $this
 * @var array $fieldLabels
 * @var mixed $hasPeriodStats
 * @var object $opponentBox
 * @var object $teamBox
 * @var \App\Model\Entity\Game $game
 */
$this->assign('title', 'Game Box Scores');
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'teamSeasons', 'action' => 'view', $game->team_season_id]) ?>">
                    Team Season
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">Game Details</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Game Box Scores</li>
        </ol>
    </nav>

    <h1 class="mb-3">Game Box Scores</h1>

    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i>
        <strong>Game:</strong>
        <?php if ($game->team_season && $game->team_season->team) : ?>
            <?= h($game->team_season->team->team_name) ?>
        <?php endif; ?>
        vs
        <?php if ($game->opponent) : ?>
            <?= h($game->opponent->opponent_name) ?>
        <?php endif; ?>
        on <?= h($game->game_date ? $game->game_date->format('M j, Y') : 'N/A') ?>
    </div>

    <?= $this->Form->create(null, ['type' => 'post']) ?>

    <div class="row g-4">
        <!-- Team Box Score -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-people-fill"></i> Team Final Stats
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php
                        $statFields = [
                            'FGM' => 'Field Goals Made',
                            'FGA' => 'Field Goals Attempted',
                            'TPM' => '3-Pointers Made',
                            'TPA' => '3-Pointers Attempted',
                            'FTM' => 'Free Throws Made',
                            'FTA' => 'Free Throws Attempted',
                            'ORB' => 'Offensive Rebounds',
                            'DRB' => 'Defensive Rebounds',
                            'RB' => 'Total Rebounds',
                            'AST' => 'Assists',
                            'STL' => 'Steals',
                            'BS' => 'Blocks',
                            'TRN' => 'Turnovers',
                            'PF' => 'Personal Fouls',
                            'TF' => 'Technical Fouls',
                            'PTS' => 'Points',
                            'PNT' => 'Points in Paint',
                            'OTO' => 'Points off Turnovers',
                            'SND' => '2nd Chance Points',
                            'FB' => 'Fast Break Points',
                            'BN' => 'Bench Points',
                            'TIED' => 'Times Tied',
                            'LC' => 'Lead Changes',
                        ];

                        foreach ($statFields as $field => $label) :
                            $displayLabel = $fieldLabels[$field] ?? $label;
                            $value = $teamBox ? $teamBox->get($field) : null;
                            ?>
                            <div class="col-md-6">
                                <?= $this->Form->control("team.{$field}", [
                                    'label' => $displayLabel,
                                    'type' => 'number',
                                    'value' => $value,
                                    'class' => 'form-control',
                                    'min' => 0,
                                    'step' => 1,
                                ]) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opponent Box Score -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-people"></i> Opponent Final Stats
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php
                        foreach ($statFields as $field => $label) :
                            $displayLabel = $fieldLabels[$field] ?? $label;
                            $value = $opponentBox ? $opponentBox->get($field) : null;
                            ?>
                            <div class="col-md-6">
                                <?= $this->Form->control("opponent.{$field}", [
                                    'label' => $displayLabel,
                                    'type' => 'number',
                                    'value' => $value,
                                    'class' => 'form-control',
                                    'min' => 0,
                                    'step' => 1,
                                ]) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Calculate default team minutes: 200 regulation + 50 per OT
    $numOT = (int)($game->ot ?? 0);
    $defaultMinutes = 200 + (50 * $numOT);
    ?>

    <div class="card mt-4" data-controller="game-box-totals-toggle">
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <?= $this->Form->checkbox('add_to_totals', [
                            'id' => 'add-to-totals-check',
                            'class' => 'form-check-input',
                            'data-game-box-totals-toggle-target' => 'checkbox',
                            'data-action' => 'change->game-box-totals-toggle#toggle',
                        ]) ?>
                        <label class="form-check-label" for="add-to-totals-check">
                            <strong>Update Season Totals</strong>
                            <div class="text-muted small">Automatically update cumulative season statistics from these box scores</div>
                        </label>
                    </div>

                    <!-- Season totals options (shown when Update Season Totals is checked) -->
                    <div id="season-totals-options" class="mt-3 ps-4" style="display: none;"
                        data-game-box-totals-toggle-target="optionsPanel">
                        <div class="card bg-light">
                            <div class="card-body py-2 px-3">
                                <div class="mb-2">
                                    <i class="bi bi-plus-circle text-success"></i>
                                    <strong class="small">+1 GP</strong>
                                    <span class="text-muted small ms-1">will be added to season totals</span>
                                </div>
                                <div>
                                    <label for="team-minutes-input" class="form-label small mb-1">
                                        <i class="bi bi-clock"></i> Team Minutes
                                    </label>
                                    <?= $this->Form->control('team_minutes', [
                                        'id' => 'team-minutes-input',
                                        'type' => 'number',
                                        'value' => $defaultMinutes,
                                        'label' => false,
                                        'class' => 'form-control form-control-sm',
                                        'min' => 0,
                                        'step' => 1,
                                        'style' => 'max-width: 120px;',
                                    ]) ?>
                                    <div class="text-muted small mt-1">
                                        Default: 200 + 50 per OT<?= $numOT > 0 ? " ({$numOT} OT = {$defaultMinutes})" : '' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <?php if ($hasPeriodStats) : ?>
                        <div class="form-check">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Period stats already entered
                            </span>
                            <a href="<?= $this->Url->build([
                                'action' => 'gameBoxPeriods', $game->id,
                            ]) ?>" class="btn btn-outline-primary btn-sm ms-2">
                                Edit Period Stats
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="form-check">
                            <?= $this->Form->checkbox('add_periods', [
                                'id' => 'add-periods-check',
                                'class' => 'form-check-input',
                            ]) ?>
                            <label class="form-check-label" for="add-periods-check">
                                Add period-by-period stats after saving
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
            <div class="d-flex gap-2">
                <a href="<?= $this->Url->build(['action' => 'edit', $game->id]) ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Box Scores
                </button>
            </div>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>
