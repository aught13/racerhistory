<?php
declare(strict_types=1);

/**
 * Games sub-navigation element.
 *
 * Displays a compact navy/gold nav bar for all game search types on every /games/ page.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes Search slug => label map
 * @var string $currentSearch Current active search type slug (empty string for index)
 */
$currentSearch = $currentSearch ?? '';
$actionMap = [
    'ranked' => 'ranked',
    'overtime' => 'overtime',
    'hundred-point' => 'hundredPoint',
    'openers' => 'openers',
    'streaks' => 'streaks',
    'margins' => 'margins',
    'series' => 'series',
];
$currentLabel = $currentSearch && isset($searchTypes[$currentSearch]) ? $searchTypes[$currentSearch] : 'Games';
?>

<nav class="navbar navbar-expand-xl rh-games-navbar" aria-label="Games type navigation" data-bs-theme="dark">
    <div class="navbar-container">
        <span class="navbar-brand games-brand"><?= h($currentLabel) ?></span>
        <button class="navbar-toggler nav-link rh-nav-toggle-link" type="button" data-bs-toggle="collapse" data-bs-target="#gamesNav"
                aria-controls="gamesNav" aria-expanded="false" aria-label="<?= h($currentLabel) ?>">
            <span class="navbar-toggler-text"><?= h($currentLabel) ?></span>
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="gamesNav">
            <ul class="navbar-nav games-nav-list">
                <li class="nav-item">
                    <a class="nav-link games-nav-link<?= $currentSearch === '' ? ' active' : '' ?>"
                    href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>"
                    <?= $currentSearch === '' ? 'aria-current="page"' : '' ?>>
                        All
                    </a>
                </li>
                <?php foreach ($searchTypes as $slug => $label) :
                    $action = $actionMap[$slug] ?? 'index';
                    $isActive = $currentSearch === $slug;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link games-nav-link<?= $isActive ? ' active' : '' ?>"
                        href="<?= $this->Url->build(['controller' => 'Games', 'action' => $action]) ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= h($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
