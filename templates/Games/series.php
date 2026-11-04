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
    $ajaxUrl = $this->PublicScope->url([
        'controller' => 'Games',
        'action' => 'series',
        '?' => ['opponent_id' => $selectedOpponent, 'format' => 'json'],
    ]);
}

$showOpponentPicker = !$selectedOpponent;
?>
<div class="container py-4" data-controller="games-search series-opponents">

    <?= $this->element('Stats/table_assets') ?>

    <h1 class="h3 mb-3">Series History</h1>

    <?php if ($selectedOpponent && $opponentName) : ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <p class="text-muted mb-0">
                Viewing series history for <strong><?= h($opponentName) ?></strong>.
            </p>
            <button
                type="button"
                id="series-opponents-picker-toggle"
                class="btn btn-outline-secondary btn-sm"
                aria-controls="series-opponents-picker-panel"
                aria-expanded="false"
            >
                Change opponent
            </button>
        </div>
    <?php endif; ?>

    <div id="series-opponents-picker-panel" class="mb-4<?= $showOpponentPicker ? '' : ' d-none' ?>">
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

        <div class="card mb-0">
            <div class="card-header">
                <strong>Opponents List</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" id="series-opponents-table-wrap">
                    <table class="table table-striped table-hover table-sm mb-0" id="series-opponents-table"
                           style="width:100%"
                           data-opponents-url="<?= h($this->PublicScope->url([
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
    </div>

    <?php if ($record && $opponentName) : ?>
        <h2 class="h4 mb-3">vs <?= h($opponentName) ?></h2>
        <?= $this->element('Games/series_summary', ['record' => $record]) ?>

        <?php if (!empty($seriesGames)) : ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Games vs <?= h($opponentName) ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="games-table-wrap">
                        <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                               <?php if ($ajaxUrl) :
                                    ?>data-ajax-url="<?= h($ajaxUrl) ?>"
                                     data-min-date="<?= h((string)($gamesDateBounds['min'] ?? '')) ?>"
                                     data-max-date="<?= h((string)($gamesDateBounds['max'] ?? '')) ?>"<?php
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
        <?php endif; ?>
    <?php elseif (!$selectedOpponent) : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Select an opponent to view the series history.
        </div>
    <?php endif; ?>
</div>
