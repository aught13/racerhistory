<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var string $hundredPointFilter
 */
$this->assign('title', '100 Point Games');
$filterLabels = [
    'all' => 'All 100+ Games',
    'team' => 'Team 100+ (Pts For)',
    'opponent' => 'Opponent 100+ (Pts Against)',
];
$ajaxUrl = $this->Url->build([
    'controller' => 'Games',
    'action' => 'hundredPoint',
    '?' => ['format' => 'json', 'filter' => $hundredPointFilter],
]);
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">100 Point Games</li>
        </ol>
    </nav>

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-4">100 Point Games</h1>
    <p class="text-muted">Games where either team scored 100 or more points.</p>

    <div class="btn-group mb-4" role="group" aria-label="100 point filter">
        <?php foreach ($filterLabels as $key => $label) : ?>
            <a href="<?= $this->Url->build([
                'controller' => 'Games',
                'action' => 'hundredPoint',
                '?' => ['filter' => $key],
            ]) ?>"
               class="btn btn-sm <?= $hundredPointFilter === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-header">
            <strong>Overall Record:</strong> <span id="games-record-display">-</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="games-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                      data-ajax-url="<?= $ajaxUrl ?>"
                       data-result-column="2">
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
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->Html->script('games-search-init', ['type' => 'module', 'ext' => '.mjs']) ?>
