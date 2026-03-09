<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var string $openerType
 */
$this->assign('title', 'Season Openers');
$openerType = $openerType ?? 'season';
$ajaxUrl = $this->Url->build([
    'controller' => 'Games',
    'action' => 'openers',
    '?' => ['format' => 'json', 'type' => $openerType],
]);

$openerLabels = [
    'season' => 'Season Opener',
    'home' => 'Home Opener',
    'conf' => 'Conference Opener',
    'conf_home' => 'Conference Home Opener',
];
?>
<div class="container py-4">

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-3">Season Openers</h1>

    <div class="btn-group mb-4" role="group" aria-label="Opener type">
        <?php foreach ($openerLabels as $key => $label) : ?>
            <a href="<?= $this->Url->build([
                'controller' => 'Games',
                'action' => 'openers',
                '?' => ['type' => $key],
            ]) ?>"
               class="btn btn-sm <?= $openerType === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
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
