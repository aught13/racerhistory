<?php
declare(strict_types=1);
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 */
$this->assign('title', '100 Point Games');
$ajaxUrl = $this->Url->build(['controller' => 'Games', 'action' => 'hundredPoint', '?' => ['format' => 'json']]);
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a></li>
            <li class="breadcrumb-item active" aria-current="page">100 Point Games</li>
        </ol>
    </nav>

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-4">100 Point Games</h1>
    <p class="text-muted">Games where either team scored 100 or more points.</p>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" id="games-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="games-results-table"
                       data-ajax-url="<?= h($ajaxUrl) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Opponent</th>
                            <th>Result</th>
                            <th>Score</th>
                            <th>H/R/N</th>
                            <th>Margin</th>
                            <th>OT</th>
                            <th>Conf</th>
                            <th>Pts For</th>
                            <th>Pts Against</th>
                            <th>Season Type</th>
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
