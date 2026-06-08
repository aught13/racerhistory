<?php
declare(strict_types=1);

/**
 * Stats sub-navigation element.
 *
 * Displays a compact navy/gold nav bar for all stat types on every /stats/ page.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $statTypes Stat slug => label map
 * @var string $statType Current active stat type slug (empty string for index)
 */
$statType = $statType ?? '';
$actionMap = [
    'player-season' => 'playerSeason',
    'team-season' => 'teamSeason',
    'team-season-opponent' => 'teamSeasonOpponent',
    'player-career' => 'playerCareer',
    'player-game' => 'playerGame',
    'team-game' => 'teamGame',
    'opponent-team-game' => 'opponentTeamGame',
    'opponent-player-game' => 'opponentPlayerGame',
];
$currentLabel = $statType && isset($statTypes[$statType]) ? $statTypes[$statType] : 'Stats';
?>

<nav class="navbar navbar-expand-xl rh-stats-navbar" aria-label="Stats type navigation" data-bs-theme="dark">
    <div class="navbar-container">
        <span class="navbar-brand stats-brand"><?= h($currentLabel) ?></span>
        <button class="navbar-toggler nav-link rh-nav-toggle-link" type="button" data-bs-toggle="collapse" data-bs-target="#statsNav"
                aria-controls="statsNav" aria-expanded="false" aria-label="<?= h($currentLabel) ?>">
            <span class="navbar-toggler-text"><?= h($currentLabel) ?></span>
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="statsNav">
            <ul class="navbar-nav stats-nav-list">
                <?php foreach ($statTypes as $slug => $label) :
                    $action = $actionMap[$slug] ?? 'index';
                    $isActive = $statType === $slug;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link stats-nav-link<?= $isActive ? ' active' : '' ?>"
                        href="<?= $this->Url->build(['controller' => 'Stats', 'action' => $action]) ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= h($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
