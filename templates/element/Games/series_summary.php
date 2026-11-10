<?php
/**
 * @var array<string,mixed> $record
 * @var \App\View\AppView $this
 */
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Overall</h6>
                <p class="card-text fs-4 fw-bold mb-0"><?= h($record['overall']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Home</h6>
                <p class="card-text fs-4 fw-bold mb-0"><?= h($record['home']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Road</h6>
                <p class="card-text fs-4 fw-bold mb-0"><?= h($record['road']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Neutral</h6>
                <p class="card-text fs-4 fw-bold mb-0"><?= h($record['neutral']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Last 10</h6>
                <p class="card-text fs-4 fw-bold mb-0"><?= h($record['last10']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Current Streak</h6>
                <p class="card-text fs-4 fw-bold mb-0">
                    <?php if ($record['streak_type']) : ?>
                        <span class="badge bg-<?= $record['streak_type'] === 'W' ? 'success' : 'danger' ?>">
                            <?= h($record['streak']) ?>
                        </span>
                    <?php else : ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Biggest Win</h6>
                <p class="card-text mb-0">
                    <?php if ($record['biggest_win']) : ?>
                        <strong><?= h($record['biggest_win_margin']) ?> pts</strong><br>
                        <small class="text-muted">
                            <?= h((string)($record['biggest_win']->game_date ?? '')) ?>
                        </small>
                    <?php else : ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Biggest Loss</h6>
                <p class="card-text mb-0">
                    <?php if ($record['biggest_loss']) : ?>
                        <strong><?= h($record['biggest_loss_margin']) ?> pts</strong><br>
                        <small class="text-muted">
                            <?= h((string)($record['biggest_loss']->game_date ?? '')) ?>
                        </small>
                    <?php else : ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php if ($record['first_game']) : ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-1">First Meeting</h6>
                    <p class="mb-0">
                        <?= h((string)($record['first_game']->game_date ?? '-')) ?>
                        &mdash;
                        <?php
                        $fg = $record['first_game'];
                        $fResult = $fg->result_flag ??
                            (!empty($fg->w) && $fg->w !== '0' ? 'W' :
                            (!empty($fg->l) && $fg->l !== '0' ? 'L' : '-'));
                        $bgClass = $fResult === 'W' ? 'success' :
                            ($fResult === 'L' ? 'danger' : 'secondary');
                        ?>
                        <span class="badge bg-<?= $bgClass ?>">
                            <?= h($fResult) ?>
                        </span>
                        <?= h($fg->pts_mur ?? '-') ?>-<?= h($fg->pts_opp ?? '-') ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-1">Last Meeting</h6>
                    <p class="mb-0">
                        <?= h((string)($record['last_game']->game_date ?? '-')) ?>
                        &mdash;
                        <?php
                        $lg = $record['last_game'];
                        $lResult = $lg->result_flag ??
                            (!empty($lg->w) && $lg->w !== '0' ? 'W' :
                            (!empty($lg->l) && $lg->l !== '0' ? 'L' : '-'));
                        $bgClass = $lResult === 'W' ? 'success' :
                            ($lResult === 'L' ? 'danger' : 'secondary');
                        ?>
                        <span class="badge bg-<?= $bgClass ?>">
                            <?= h($lResult) ?>
                        </span>
                        <?= h($lg->pts_mur ?? '-') ?>-<?= h($lg->pts_opp ?? '-') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
