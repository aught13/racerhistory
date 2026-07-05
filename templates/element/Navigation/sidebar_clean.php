<?php
declare(strict_types=1);

/**
 * Unified public navigation.
 *
 * @var \App\View\AppView $this
 */

$currentTarget = (string)$this->getRequest()->getRequestTarget();
$navLinkAttributes = static function (string $url) use ($currentTarget): array {
    $attributes = [
        'class' => 'nav-link' . ($currentTarget === $url ? ' active' : ''),
    ];

    if ($currentTarget === $url) {
        $attributes['aria-current'] = 'page';
    }

    return $attributes;
};

$statsLinks = [
    ['label' => 'Player Season', 'url' => '/stats/player-season'],
    ['label' => 'Player Career', 'url' => '/stats/player-career'],
    ['label' => 'Team Season', 'url' => '/stats/team-season'],
    ['label' => 'Team Season Opponent', 'url' => '/stats/team-season-opponent'],
    ['label' => 'Team Game', 'url' => '/stats/team-game'],
    ['label' => 'Opponent Team Game', 'url' => '/stats/opponent-team-game'],
    ['label' => 'Player Game', 'url' => '/stats/player-game'],
    ['label' => 'Opponent Player Game', 'url' => '/stats/opponent-player-game'],
];

$rankedLinks = [
    ['label' => 'All Ranked', 'url' => '/games/ranked/:filter?filter=all'],
    ['label' => 'Team Ranked', 'url' => '/games/ranked/:filter?filter=team'],
    ['label' => 'Opponent Ranked', 'url' => '/games/ranked/:filter?filter=opponent'],
];

$hundredPointLinks = [
    ['label' => 'All 100+ Games', 'url' => '/games/hundred-point/'],
    ['label' => 'Team 100+ (Pts For)', 'url' => '/games/hundred-point/:filter?filter=team'],
    ['label' => 'Opponent 100+ (Pts Against)', 'url' => '/games/hundred-point/:filter?filter=opponent'],
];

$openersLinks = [
    ['label' => 'Season Opener', 'url' => '/games/openers'],
    ['label' => 'Home Opener', 'url' => '/games/openers/:type?type=home'],
    ['label' => 'Conference Opener', 'url' => '/games/openers/:type?type=conf'],
    ['label' => 'Conference Home Opener', 'url' => '/games/openers/:type?type=conf_home'],
];

$streaksWinningLinks = [
    ['label' => 'Overall', 'url' => '/games/streaks?result=W'],
    ['label' => 'Home', 'url' => '/games/streaks?result=W&filter=home'],
    ['label' => 'Road', 'url' => '/games/streaks?result=W&filter=road'],
    ['label' => 'Conf Overall', 'url' => '/games/streaks?result=W&filter=conf'],
    ['label' => 'Conf Home', 'url' => '/games/streaks?result=W&filter=conf_home'],
    ['label' => 'Conf Road', 'url' => '/games/streaks?result=W&filter=conf_road'],
];

$streaksLosingLinks = [
    ['label' => 'Overall', 'url' => '/games/streaks?result=L'],
    ['label' => 'Home', 'url' => '/games/streaks?result=L&filter=home'],
    ['label' => 'Road', 'url' => '/games/streaks?result=L&filter=road'],
    ['label' => 'Conf Overall', 'url' => '/games/streaks?result=L&filter=conf'],
    ['label' => 'Conf Home', 'url' => '/games/streaks?result=L&filter=conf_home'],
    ['label' => 'Conf Road', 'url' => '/games/streaks?result=L&filter=conf_road'],
];

$marginsWinningLinks = [
    ['label' => 'Overall', 'url' => '/games/margins?type=win&filter=overall'],
    ['label' => 'Home', 'url' => '/games/margins?type=win&filter=home'],
    ['label' => 'Road', 'url' => '/games/margins?type=win&filter=road'],
    ['label' => 'Neutral', 'url' => '/games/margins?type=win&filter=neutral'],
    ['label' => 'Conf Overall', 'url' => '/games/margins?type=win&filter=conf'],
    ['label' => 'Conf Home', 'url' => '/games/margins?type=win&filter=conf_home'],
    ['label' => 'Conf Road', 'url' => '/games/margins?type=win&filter=conf_road'],
];

$marginsLosingLinks = [
    ['label' => 'Overall', 'url' => '/games/margins?type=loss&filter=overall'],
    ['label' => 'Home', 'url' => '/games/margins?type=loss&filter=home'],
    ['label' => 'Road', 'url' => '/games/margins?type=loss&filter=road'],
    ['label' => 'Neutral', 'url' => '/games/margins?type=loss&filter=neutral'],
    ['label' => 'Conf Overall', 'url' => '/games/margins?type=loss&filter=conf'],
    ['label' => 'Conf Home', 'url' => '/games/margins?type=loss&filter=conf_home'],
    ['label' => 'Conf Road', 'url' => '/games/margins?type=loss&filter=conf_road'],
];
?>

<nav class="navbar navbar-expand-lg rh-navbar" data-bs-theme="dark" aria-label="Primary navigation">
    <div class="navbar-container">
        <a class="navbar-brand rh-logo-link" href="<?= $this->Url->build('/') ?>" aria-label="RacerHistory Home">
            <img src="<?= $this->Url->build('/img/logo.png') ?>" alt="" style="max-height: 32px; object-fit: contain;" loading="eager" class="rh-nav-logo">
        </a>
        <button class="navbar-toggler nav-link rh-nav-toggle-link" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-text">Menu</span>
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse rh-unified-nav-collapse" id="navbarNav" data-controller="nav-accordion">
            <ul class="navbar-nav rh-nav-list w-100 gap-1">
                <li class="nav-item rh-nav-item rh-nav-item--link">
                    <?= $this->Html->link('News', '/blog', $navLinkAttributes('/blog')) ?>
                </li>
                <li class="nav-item rh-nav-item rh-nav-item--link">
                    <?= $this->Html->link('Seasons', '/seasons', $navLinkAttributes('/seasons')) ?>
                </li>
                <li class="nav-item rh-nav-item rh-nav-item--link">
                    <?= $this->Html->link('People', '/people', $navLinkAttributes('/people')) ?>
                </li>

                <li class="nav-item rh-nav-item rh-nav-item--menu rh-nav-item--stats">
                    <button
                        type="button"
                        class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start"
                        data-nav-accordion-target="toggle"
                        data-nav-accordion-prefix="/stats"
                        data-action="click->nav-accordion#toggle"
                        aria-controls="stats-panel"
                        aria-expanded="false">
                        <span>Stats</span>
                        <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                    </button>
                    <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="stats-panel" hidden>
                        <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                            <?= $this->Html->link('Stats Home', '/stats', $navLinkAttributes('/stats')) ?>
                            <?php foreach ($statsLinks as $link) : ?>
                                <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>

                <li class="nav-item rh-nav-item rh-nav-item--menu rh-nav-item--games">
                    <button
                        type="button"
                        class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start"
                        data-nav-accordion-target="toggle"
                        data-nav-accordion-prefix="/games"
                        data-action="click->nav-accordion#toggle"
                        aria-controls="games-panel"
                        aria-expanded="false">
                        <span>Games</span>
                        <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                    </button>
                    <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-panel" hidden>
                        <div class="rh-nav-submenu-links d-flex flex-column gap-2">
                            <?= $this->Html->link('Games Home', '/games', $navLinkAttributes('/games')) ?>
                            <?= $this->Html->link('All Games', '/games/all-games', $navLinkAttributes('/games/all-games')) ?>

                            <div class="rh-nav-subgroup rh-nav-subgroup--level2">
                                <button
                                    type="button"
                                    class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/ranked"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="games-ranked-panel"
                                    aria-expanded="false">
                                    <span>Ranked Games</span>
                                    <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                </button>
                                <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-ranked-panel" hidden>
                                    <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                        <?php foreach ($rankedLinks as $link) : ?>
                                            <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <?= $this->Html->link('Overtime Games', '/games/overtime', $navLinkAttributes('/games/overtime')) ?>

                            <div class="rh-nav-subgroup rh-nav-subgroup--level2">
                                <button
                                    type="button"
                                    class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/hundred-point"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="games-hundred-point-panel"
                                    aria-expanded="false">
                                    <span>100 Point Games</span>
                                    <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                </button>
                                <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-hundred-point-panel" hidden>
                                    <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                        <?php foreach ($hundredPointLinks as $link) : ?>
                                            <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="rh-nav-subgroup rh-nav-subgroup--level2">
                                <button
                                    type="button"
                                    class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/openers"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="games-openers-panel"
                                    aria-expanded="false">
                                    <span>Season Openers</span>
                                    <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                </button>
                                <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-openers-panel" hidden>
                                    <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                        <?php foreach ($openersLinks as $link) : ?>
                                            <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="rh-nav-subgroup rh-nav-subgroup--level2">
                                <button
                                    type="button"
                                    class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/streaks"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="games-streaks-panel"
                                    aria-expanded="false">
                                    <span>Streaks</span>
                                    <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                </button>
                                <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-streaks-panel" hidden>
                                    <div class="rh-nav-submenu-links d-flex flex-column gap-2">
                                        <div class="rh-nav-subgroup rh-nav-subgroup--level3">
                                            <button
                                                type="button"
                                                class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                                data-nav-accordion-target="toggle"
                                                data-nav-accordion-prefix="/games/streaks?result=W"
                                                data-action="click->nav-accordion#toggle"
                                                aria-controls="games-streaks-winning-panel"
                                                aria-expanded="false">
                                                <span>Winning</span>
                                                <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                            </button>
                                            <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-streaks-winning-panel" hidden>
                                                <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                                    <?php foreach ($streaksWinningLinks as $link) : ?>
                                                        <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rh-nav-subgroup rh-nav-subgroup--level3">
                                            <button
                                                type="button"
                                                class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                                data-nav-accordion-target="toggle"
                                                data-nav-accordion-prefix="/games/streaks?result=L"
                                                data-action="click->nav-accordion#toggle"
                                                aria-controls="games-streaks-losing-panel"
                                                aria-expanded="false">
                                                <span>Losing</span>
                                                <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                            </button>
                                            <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-streaks-losing-panel" hidden>
                                                <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                                    <?php foreach ($streaksLosingLinks as $link) : ?>
                                                        <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rh-nav-subgroup rh-nav-subgroup--level2">
                                <button
                                    type="button"
                                    class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/margins"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="games-margins-panel"
                                    aria-expanded="false">
                                    <span>Margins</span>
                                    <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                </button>
                                <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-margins-panel" hidden>
                                    <div class="rh-nav-submenu-links d-flex flex-column gap-2">
                                        <div class="rh-nav-subgroup rh-nav-subgroup--level3">
                                            <button
                                                type="button"
                                                class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                                data-nav-accordion-target="toggle"
                                                data-nav-accordion-prefix="/games/margins?result=W"
                                                data-action="click->nav-accordion#toggle"
                                                aria-controls="games-margins-winning-panel"
                                                aria-expanded="false">
                                                <span>Winning</span>
                                                <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                            </button>
                                            <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-margins-winning-panel" hidden>
                                                <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                                    <?php foreach ($marginsWinningLinks as $link) : ?>
                                                        <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rh-nav-subgroup rh-nav-subgroup--level3">
                                            <button
                                                type="button"
                                                class="nav-link rh-accordion-toggle rh-nav-parent-toggle d-flex align-items-center justify-content-between w-100 border-0 bg-transparent text-start ps-2"
                                                data-nav-accordion-target="toggle"
                                                data-nav-accordion-prefix="/games/margins?result=L"
                                                data-action="click->nav-accordion#toggle"
                                                aria-controls="games-margins-losing-panel"
                                                aria-expanded="false">
                                                <span>Losing</span>
                                                <i class="bi bi-caret-down-fill nav-accordion-caret" aria-hidden="true"></i>
                                            </button>
                                            <div class="rh-nav-panel d-none ps-3 pt-2 pb-1" data-nav-accordion-target="panel" id="games-margins-losing-panel" hidden>
                                                <div class="rh-nav-submenu-links d-flex flex-column gap-1">
                                                    <?php foreach ($marginsLosingLinks as $link) : ?>
                                                        <?= $this->Html->link($link['label'], $link['url'], $navLinkAttributes($link['url'])) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?= $this->Html->link('Series History', '/games/series-history', $navLinkAttributes('/games/series-history')) ?>
                        </div>
                    </div>
                </li>

                <li class="nav-item ms-auto">
                    <div class="rh-theme-toggle"></div>
                </li>
            </ul>
        </div>
    </div>
</nav>
