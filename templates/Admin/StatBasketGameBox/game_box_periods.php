<?php
/**
 * Period-by-Period Box Score Entry Template
 *
 * @var \App\View\AppView $this
 * @var array $existingStats
 * @var array $fieldLabels
 * @var mixed $numOT
 * @var mixed $numPeriods
 * @var \App\Model\Entity\Game $game
 */
$this->assign('title', 'Period Box Scores');
?>
<div class="container-fluid py-4">
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
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['action' => 'gameBox', $game->id]) ?>">Game Box</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Period Stats</li>
        </ol>
    </nav>

    <h1 class="mb-3">Period-by-Period Box Scores</h1>

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
        <br>
        <small>Periods: <?= h($numPeriods) ?> | Overtime Periods: <?= h($numOT) ?></small>
    </div>

    <?= $this->Form->create(null, ['type' => 'post']) ?>

    <?php
    $statFields = [
        'FGM' => 'FGM',
        'FGA' => 'FGA',
        'TPM' => '3PM',
        'TPA' => '3PA',
        'FTM' => 'FTM',
        'FTA' => 'FTA',
        'ORB' => 'ORB',
        'DRB' => 'DRB',
        'RB' => 'REB',
        'AST' => 'AST',
        'STL' => 'STL',
        'BS' => 'BLK',
        'TRN' => 'TO',
        'PF' => 'PF',
        'PTS' => 'PTS',
    ];

    // Regular periods
    for ($p = 1; $p <= $numPeriods; $p++) :
        $periodLabel = $numPeriods == 2 ? "Half $p" : "Quarter $p";
        ?>
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0"><?= h($periodLabel) ?></h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Team Stats -->
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-people-fill"></i> Team
                        </h5>
                        <div class="row g-2">
                            <?php foreach ($statFields as $field => $shortLabel) :
                                $displayLabel = $fieldLabels[$field] ?? $shortLabel;
                                $existingKey = 'team_' . $p;
                                $value = isset($existingStats[$existingKey])
                                    ? $existingStats[$existingKey]->get($field) : null;
                                ?>
                                <div class="col-md-4 col-sm-6">
                                    <?= $this->Form->control("team_{$p}.{$field}", [
                                        'label' => $displayLabel,
                                        'type' => 'number',
                                        'value' => $value,
                                        'class' => 'form-control form-control-sm',
                                        'min' => 0,
                                        'step' => 1,
                                    ]) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Opponent Stats -->
                    <div class="col-md-6">
                        <h5 class="text-danger mb-3">
                            <i class="bi bi-people"></i> Opponent
                        </h5>
                        <div class="row g-2">
                            <?php foreach ($statFields as $field => $shortLabel) :
                                $displayLabel = $fieldLabels[$field] ?? $shortLabel;
                                $existingKey = 'opponent_' . $p;
                                $value = isset($existingStats[$existingKey])
                                    ? $existingStats[$existingKey]->get($field) : null;
                                ?>
                                <div class="col-md-4 col-sm-6">
                                    <?= $this->Form->control("opponent_{$p}.{$field}", [
                                        'label' => $displayLabel,
                                        'type' => 'number',
                                        'value' => $value,
                                        'class' => 'form-control form-control-sm',
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
    <?php endfor; ?>

    <?php
    // Overtime periods
    for ($ot = 1; $ot <= $numOT; $ot++) :
        $otLabel = $ot == 1 ? 'OT' : "OT{$ot}";
        $otPeriod = $otLabel;
        ?>
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><?= h($otLabel) ?></h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Team OT Stats -->
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-people-fill"></i> Team
                        </h5>
                        <div class="row g-2">
                            <?php foreach ($statFields as $field => $shortLabel) :
                                $displayLabel = $fieldLabels[$field] ?? $shortLabel;
                                $existingKey = 'team_' . $otPeriod;
                                $value = isset($existingStats[$existingKey]) ? $existingStats[$existingKey]->get($field) : null;
                                ?>
                                <div class="col-md-4 col-sm-6">
                                    <?= $this->Form->control("team_{$otPeriod}.{$field}", [
                                        'label' => $displayLabel,
                                        'type' => 'number',
                                        'value' => $value,
                                        'class' => 'form-control form-control-sm',
                                        'min' => 0,
                                        'step' => 1,
                                    ]) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Opponent OT Stats -->
                    <div class="col-md-6">
                        <h5 class="text-danger mb-3">
                            <i class="bi bi-people"></i> Opponent
                        </h5>
                        <div class="row g-2">
                            <?php foreach ($statFields as $field => $shortLabel) :
                                $displayLabel = $fieldLabels[$field] ?? $shortLabel;
                                $existingKey = 'opponent_' . $otPeriod;
                                $value = isset($existingStats[$existingKey]) ? $existingStats[$existingKey]->get($field) : null;
                                ?>
                                <div class="col-md-4 col-sm-6">
                                    <?= $this->Form->control("opponent_{$otPeriod}.{$field}", [
                                        'label' => $displayLabel,
                                        'type' => 'number',
                                        'value' => $value,
                                        'class' => 'form-control form-control-sm',
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
    <?php endfor; ?>

    <div class="card mt-4">
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="<?= $this->Url->build(['action' => 'gameBox', $game->id]) ?>" class="btn btn-secondary">
                Back to Final Stats
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save Period Stats
            </button>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>
