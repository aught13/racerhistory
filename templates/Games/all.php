<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 */
$this->assign('title', 'All Games');
$ajaxUrl = $this->Url->build([
    'controller' => 'Games',
    'action' => 'all',
    '?' => ['format' => 'json'],
]);
?>
<div class="container py-4">


    <h1 class="h3 mb-4">All Games</h1>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-header">
            <strong>Overall Record:</strong> <span id="games-record-display">-</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="games-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                       style="width:100%"
                       data-ajax-url="<?= h($ajaxUrl) ?>"
                       data-result-column="2"
                       data-min-date="<?= h((string)($gamesDateBounds['min'] ?? '')) ?>"
                       data-max-date="<?= h((string)($gamesDateBounds['max'] ?? '')) ?>"
                       data-weekday-column="11"
                       data-searchbuilder-columns="0,1,2,3,4,5,6,7,8,9,10,11">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Opponent</th>
                            <th>Result</th>
                            <th>Margin</th>
                            <th>Pts For</th>
                            <th>Pts Against</th>
                            <th>OT</th>
                            <th>H/R/N</th>
                            <th>Conf</th>
                            <th>Game Type</th>
                            <th>Season</th>
                            <th>Day of Week</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
