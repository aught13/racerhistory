<?php
declare(strict_types=1);

/**
 * Player Career Stats
 *
 * @var \App\View\AppView $this
 * @var string $statType
 * @var string $statTypeLabel
 * @var string $currentSport
 * @var array<string, string> $statTypes
 */
$this->assign('title', $statTypeLabel . ' Stats');

$actionMap = [
    'player-season' => 'playerSeason',
    'team-season' => 'teamSeason',
    'team-season-opponent' => 'teamSeasonOpponent',
    'player-career' => 'playerCareer',
    'player-game' => 'playerGame',
    'team-game' => 'teamGame',
    'opponent-player-game' => 'opponentPlayerGame',
];
$ajaxAction = $actionMap[$statType] ?? 'index';
$ajaxUrl = $this->Url->build(['controller' => 'Stats', 'action' => $ajaxAction, '?' => ['sport' => $currentSport, 'format' => 'json']]);
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'index']) ?>">Statistics</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= h($statTypeLabel) ?></li>
        </ol>
    </nav>

    <?= $this->element('Stats/sub_nav', ['statTypes' => $statTypes, 'statType' => $statType]) ?>

    <h1 class="h3 mb-4"><?= h($statTypeLabel) ?> Stats</h1>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" id="stats-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="stats-results-table"
                       data-ajax-url="<?= h($ajaxUrl) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Player</th>
                            <th>Seasons</th>
                            <th>GP</th>
                            <th>GS</th>
                            <th>MIN</th>
                            <th>FGM</th>
                            <th>FGA</th>
                            <th>3PM</th>
                            <th>3PA</th>
                            <th>FTM</th>
                            <th>FTA</th>
                            <th>ORB</th>
                            <th>DRB</th>
                            <th>RB</th>
                            <th>AST</th>
                            <th>STL</th>
                            <th>BS</th>
                            <th>TRN</th>
                            <th>PF</th>
                            <th>PTS</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->Html->script('stats-init-loader', ['type' => 'module', 'ext' => '.mjs']) ?>
