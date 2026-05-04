<?php
declare(strict_types=1);

/**
 * Team Game Stats
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

    <h1 class="h3 mb-4"><?= h($statTypeLabel) ?> Stats</h1>

    <?= $this->element('Stats/table_assets') ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" id="stats-table-wrap">
                <table class="table table-striped table-hover table-sm mb-0" id="stats-results-table"
                       data-ajax-url="<?= h($ajaxUrl) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Opponent</th>
                            <th>Date</th>
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
<?php
$statsInitPath = WWW_ROOT . 'js' . DS . 'stats-init-loader.mjs';
$statsInitVer = file_exists($statsInitPath) ? (filemtime($statsInitPath) ?: 0) : 0;
?>
<script type="module" src="/js/stats-init-loader.mjs?v=<?= $statsInitVer ?>"></script>
