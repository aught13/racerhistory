<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var array<int, string> $opponents
 * @var int|null $selectedOpponent
 * @var string|null $opponentName
 * @var array|null $record
 * @var array|null $seriesGames
 */
$this->assign('title', 'Series History');
$selectedOpponent = $selectedOpponent ?? null;
$record = $record ?? null;
$seriesGames = $seriesGames ?? null;
$opponentName = $opponentName ?? null;

$ajaxUrl = null;
if ($selectedOpponent) {
    $ajaxUrl = $this->Url->build([
        'controller' => 'Games',
        'action' => 'series',
        '?' => ['opponent_id' => $selectedOpponent, 'format' => 'json'],
    ]);
}
?>
<div class="container py-4">

    <?= $this->element('Stats/table_assets') ?>

    <h1 class="h3 mb-3">Series History</h1>

    <div class="mb-4">
        <label for="series-opponents-search" class="form-label">Search Opponents</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input
                type="text"
                id="series-opponents-search"
                class="form-control"
                placeholder="Search by opponent name or abbreviation..."
                autocomplete="off"
            />
        </div>
        <div class="form-text">Select an opponent from the list to view series history.</div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Opponents List</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="series-opponents-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="series-opponents-table"
                       style="width:100%"
                       data-opponents-url="<?= h($this->Url->build([
                           'controller' => 'Games',
                           'action' => 'seriesOpponents',
                           '?' => ['format' => 'json'],
                       ])) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Opponent</th>
                            <th>Short</th>
                            <th>Games</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($record && $opponentName) : ?>
        <h2 class="h4 mb-3">vs <?= h($opponentName) ?></h2>

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

        <?php if (!empty($seriesGames)) : ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Games vs <?= h($opponentName) ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="games-table-wrap">
                        <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                               <?php if ($ajaxUrl) :
                                    ?>data-ajax-url="<?= h($ajaxUrl) ?>"<?php
                               endif; ?>>
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Result</th>
                                    <th>Score</th>
                                    <th>H/R/N</th>
                                    <th>Margin</th>
                                    <th>Pts For</th>
                                    <th>Pts Against</th>
                                    <th>Season</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?= $this->Html->script('games-search-init', ['type' => 'module', 'ext' => '.mjs']) ?>
        <?php endif; ?>
    <?php elseif (!$selectedOpponent) : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Select an opponent to view the series history.
        </div>
    <?php endif; ?>
</div>
<?= $this->Html->script('games-series-opponents-init', ['type' => 'module', 'ext' => '.mjs']) ?>
