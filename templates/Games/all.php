<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 */
$this->assign('title', 'All Games');
$ajaxUrl = $this->PublicScope->url([
    'controller' => 'Games',
    'action' => 'all',
    '?' => ['format' => 'json'],
]);
$publicScope = $publicScope ?? ['sportKey' => null, 'teamId' => null];
$showTeamSelectionPrompt = ($publicScope['teamId'] ?? null) === null;
?>
<div class="container py-4" data-controller="games-search">


    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0">All Games</h1>
        <?php if ($showTeamSelectionPrompt) : ?>
            <div class="rh-games-team-selection-note border border-secondary-subtle rounded px-3 py-2 text-secondary" aria-disabled="true">
                <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                Select a team in the sport/team bar to unlock more options.
            </div>
        <?php endif; ?>
    </div>

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
                       data-result-column="3"
                       data-min-date="<?= h((string)($gamesDateBounds['min'] ?? '')) ?>"
                       data-max-date="<?= h((string)($gamesDateBounds['max'] ?? '')) ?>"
                       data-weekday-column="12"
                       data-searchbuilder-columns="0,1,2,3,4,5,6,7,8,9,10,11,12">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Team</th>
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
