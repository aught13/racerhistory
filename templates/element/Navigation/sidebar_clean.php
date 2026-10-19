<?php
declare(strict_types=1);

/**
 * Unified public navigation.
 *
 * @var \App\View\AppView $this
 * @var array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $publicScope
 * @var array<string,string> $publicSportOptions
 * @var array<int,array{label:string,sportKey:string}> $publicTeamOptions
 * @var bool $showPublicSportSelector
 * @var bool $showPublicTeamSelector
 * @var bool $isStatsController
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

$publicScope = $publicScope ?? ['sportKey' => null, 'teamId' => null];
$scopeSport = $publicScope['sportKey'] ?? 'all';
$scopeTeam = $publicScope['teamId'] === null ? 'all' : (string)$publicScope['teamId'];
$showPublicSportSelector = $showPublicSportSelector ?? false;
$showPublicTeamSelector = $showPublicTeamSelector ?? false;
$isStatsController = $isStatsController ?? false;
$publicSportOptions = $publicSportOptions ?? [];
$publicTeamOptions = $publicTeamOptions ?? [];
$currentPath = (string)$this->getRequest()->getUri()->getPath();
$preservedQuery = $this->getRequest()->getQueryParams();
unset($preservedQuery['sport'], $preservedQuery['team'], $preservedQuery['gender'], $preservedQuery['show_all_history']);
$renderHiddenQuery = function (string $name, mixed $value) use (&$renderHiddenQuery): string {
    if (is_array($value)) {
        $html = '';
        foreach ($value as $key => $childValue) {
            $html .= $renderHiddenQuery($name . '[' . $key . ']', $childValue);
        }

        return $html;
    }

    if (!is_scalar($value)) {
        return '';
    }

    return '<input type="hidden" name="' . h($name) . '" value="' . h((string)$value) . '">';
};
$preservedQueryHtml = '';
foreach ($preservedQuery as $queryName => $queryValue) {
    $preservedQueryHtml .= $renderHiddenQuery((string)$queryName, $queryValue);
}
$withPublicScope = static function (string $url) use ($scopeSport, $scopeTeam): string {
    $parts = parse_url($url);
    if ($parts === false) {
        return $url;
    }

    $query = [];
    parse_str((string)($parts['query'] ?? ''), $query);
    $query['sport'] = $scopeSport;
    $query['team'] = $scopeTeam;

    return (string)($parts['path'] ?? '/') . '?' . http_build_query($query);
};
$navLink = function (
    string $label,
    string $url,
) use (
    $withPublicScope,
    $navLinkAttributes,
    $scopeSport,
): string {
    if (str_starts_with($url, '/stats/') && $scopeSport === 'all') {
        $url = '/stats';
    }
    $scopedUrl = $withPublicScope($url);

    return $this->Html->link($label, $scopedUrl, $navLinkAttributes($scopedUrl));
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
        <a class="navbar-brand rh-logo-link" href="<?= $this->Url->build('/?' . http_build_query(['sport' => $scopeSport, 'team' => $scopeTeam])) ?>" aria-label="RacerHistory Home">
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
                    <?= $navLink('News', '/blog') ?>
                </li>
                <li class="nav-item rh-nav-item rh-nav-item--link">
                    <?= $navLink('Seasons', '/seasons') ?>
                </li>
                <li class="nav-item rh-nav-item rh-nav-item--link">
                    <?= $navLink('People', '/people') ?>
                </li>

                <?php if ($showPublicSportSelector || $showPublicTeamSelector) : ?>
                    <li class="nav-item rh-nav-item rh-nav-item--scope">
                        <form method="get" action="<?= h($currentPath) ?>" class="rh-nav-scope d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-1"
                              data-controller="public-sport-scope"
                              data-public-sport-scope-sport-key-value="<?= h($scopeSport) ?>"
                              data-public-sport-scope-team-id-value="<?= h($scopeTeam) ?>"
                              data-action="submit->public-sport-scope#submit">
                            <?= $preservedQueryHtml ?>
                            <?php if ($showPublicSportSelector) : ?>
                                <label class="visually-hidden" for="public-scope-sport">Sport</label>
                                <select id="public-scope-sport" name="sport" class="form-select form-select-sm"
                                        data-public-sport-scope-target="sport"
                                        data-action="change->public-sport-scope#changeSport">
                                    <?php if ($isStatsController) : ?>
                                        <option value=""<?= $scopeSport === 'all' ? ' selected' : '' ?> disabled>Choose a sport</option>
                                    <?php else : ?>
                                        <option value="all"<?= $scopeSport === 'all' ? ' selected' : '' ?>>All sports</option>
                                    <?php endif; ?>
                                    <?php foreach ($publicSportOptions as $sportKey => $sportName) : ?>
                                        <option value="<?= h($sportKey) ?>"<?= $scopeSport === $sportKey ? ' selected' : '' ?>><?= h($sportName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="hidden" name="sport" value="<?= h($scopeSport) ?>"
                                       data-public-sport-scope-target="sport">
                            <?php endif; ?>
                            <?php if ($showPublicTeamSelector) : ?>
                                <label class="visually-hidden" for="public-scope-team">Team</label>
                                <select id="public-scope-team" name="team" class="form-select form-select-sm"
                                        data-public-sport-scope-target="team"
                                        data-action="change->public-sport-scope#changeTeam">
                                    <option value="all" data-sport-key="all"<?= $scopeTeam === 'all' ? ' selected' : '' ?>>All teams</option>
                                    <?php foreach ($publicTeamOptions as $teamId => $teamOption) : ?>
                                        <option value="<?= (int)$teamId ?>" data-sport-key="<?= h($teamOption['sportKey']) ?>"<?= $scopeTeam === (string)$teamId ? ' selected' : '' ?>><?= h($teamOption['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="hidden" name="team" value="<?= h($scopeTeam) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-outline-light">Apply</button>
                        </form>
                    </li>
                <?php else : ?>
                    <li class="d-none" data-controller="public-sport-scope"
                        data-public-sport-scope-sport-key-value="<?= h($scopeSport) ?>"
                        data-public-sport-scope-team-id-value="<?= h($scopeTeam) ?>"></li>
                <?php endif; ?>

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
                            <?= $navLink('Stats Home', '/stats') ?>
                            <?php foreach ($statsLinks as $link) : ?>
                                <?= $navLink($link['label'], $link['url']) ?>
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
                            <?= $navLink('Games Home', '/games') ?>
                            <?= $navLink('All Games', '/games/all-games') ?>

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
                                            <?= $navLink($link['label'], $link['url']) ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <?= $navLink('Overtime Games', '/games/overtime') ?>

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
                                            <?= $navLink($link['label'], $link['url']) ?>
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
                                            <?= $navLink($link['label'], $link['url']) ?>
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
                                                        <?= $navLink($link['label'], $link['url']) ?>
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
                                                        <?= $navLink($link['label'], $link['url']) ?>
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
                                                        <?= $navLink($link['label'], $link['url']) ?>
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
                                                        <?= $navLink($link['label'], $link['url']) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?= $navLink('Series History', '/games/series-history') ?>
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
