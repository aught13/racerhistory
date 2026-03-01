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
    $ajaxUrl = $this->Url->build(['controller' => 'Games', 'action' => 'series', '?' => ['opponent_id' => $selectedOpponent, 'format' => 'json']]);
}
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a></li>
            <li class="breadcrumb-item active" aria-current="page">Series History</li>
        </ol>
    </nav>

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-3">Series History</h1>

    <form method="get" action="<?= $this->Url->build(['controller' => 'Games', 'action' => 'series']) ?>" class="row g-2 mb-4 align-items-end">
        <div class="col-md-6">
            <label for="opponent-select" class="form-label">Search by Opponent</label>
            <select name="opponent_id" id="opponent-select" class="form-select">
                <option value="">-- Select Opponent --</option>
                <?php foreach ($opponents as $id => $name) : ?>
                    <option value="<?= (int)$id ?>" <?= $selectedOpponent === $id ? 'selected' : '' ?>>
                        <?= h($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-1"></i> Search
            </button>
        </div>
    </form>

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
                                    ((!empty($fg->w) && $fg->w !== '0') ? 'W' :
                                    ((!empty($fg->l) && $fg->l !== '0') ? 'L' : '-'));
                                ?>
                                <span class="badge bg-<?= $fResult === 'W' ? 'success' : ($fResult === 'L' ? 'danger' : 'secondary') ?>">
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
                                    ((!empty($lg->w) && $lg->w !== '0') ? 'W' :
                                    ((!empty($lg->l) && $lg->l !== '0') ? 'L' : '-'));
                                ?>
                                <span class="badge bg-<?= $lResult === 'W' ? 'success' : ($lResult === 'L' ? 'danger' : 'secondary') ?>">
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
            <?= $this->element('Stats/table_assets') ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Games vs <?= h($opponentName) ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="games-table-wrap">
                        <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                               <?php if ($ajaxUrl) : ?>data-ajax-url="<?= h($ajaxUrl) ?>"<?php endif; ?>>
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
