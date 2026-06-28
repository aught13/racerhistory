<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var string $rankedFilter
 */
$this->assign('title', 'Ranked Games');
$filterLabels = [
    'all' => 'All Ranked',
    'team' => 'Team Ranked',
    'opponent' => 'Opponent Ranked',
];
?>
<div class="container py-4">


    <h1 class="h3 mb-3">Ranked Games</h1>
    <p class="text-muted">Games vs a ranked opponent or as a ranked team.</p>

    <div class="btn-group mb-4" role="group" aria-label="Ranked filter">
        <?php foreach ($filterLabels as $key => $label) : ?>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'ranked', '?' => ['filter' => $key]]) ?>"
               class="btn btn-sm <?= $rankedFilter === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold" id="games-record-display">Loading...</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="games-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                      data-ajax-url="<?= $this->Url->build(['controller' => 'Games', 'action' => 'ranked', '?' => ['format' => 'json', 'filter' => $rankedFilter]]) ?>"
                      data-min-date="<?= h((string)($gamesDateBounds['min'] ?? '')) ?>"
                      data-max-date="<?= h((string)($gamesDateBounds['max'] ?? '')) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Team Rk</th>
                            <th>Team</th>
                            <th>Opponent</th>
                            <th>Opp Rk</th>
                            <th>H/R/N</th>
                            <th>W/L</th>
                            <th>Margin</th>
                            <th>Pts For</th>
                            <th>Pts Against</th>
                            <th>Season</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
