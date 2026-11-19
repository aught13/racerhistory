<?php
declare(strict_types=1);

/**
 * Public sport/team scope sub-navigation.
 *
 * @var \App\View\AppView $this
 * @var array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $publicScope
 * @var array<string,string> $publicSportOptions
 * @var array<int,array{label:string,sportKey:string}> $publicTeamOptions
 * @var bool $showPublicSportSelector
 * @var bool $showPublicTeamSelector
 * @var bool $isStatsController
 * @var string|null $publicScopeTeamLabel
 * @var string|null $publicScopeSportLabel
 */
$publicScope = $publicScope ?? ['sportKey' => null, 'teamId' => null];
$publicSportOptions = $publicSportOptions ?? [];
$publicTeamOptions = $publicTeamOptions ?? [];
$scopeSport = $publicScope['sportKey'] ?? 'all';
$scopeTeam = $publicScope['teamId'] === null ? 'all' : (string)$publicScope['teamId'];
$publicScopeTeamLabel = $publicScopeTeamLabel ?? null;
$publicScopeSportLabel = $publicScopeSportLabel ?? null;
$allTeamsLabel = 'All teams';

$currentScopeLabel = $allTeamsLabel;
if ($scopeTeam !== 'all' && is_string($publicScopeTeamLabel) && $publicScopeTeamLabel !== '') {
    $currentScopeLabel = $publicScopeTeamLabel;
} elseif ($scopeTeam !== 'all' && isset($publicTeamOptions[(int)$scopeTeam])) {
    $currentScopeLabel = $publicTeamOptions[(int)$scopeTeam]['label'];
} elseif ($scopeSport !== 'all' && is_string($publicScopeSportLabel) && $publicScopeSportLabel !== '') {
    $currentScopeLabel = $publicScopeSportLabel;
} elseif ($scopeSport !== 'all' && isset($publicSportOptions[$scopeSport])) {
    $currentScopeLabel = $publicSportOptions[$scopeSport];
}

$currentPath = (string)$this->getRequest()->getUri()->getPath();
$preservedQuery = $this->getRequest()->getQueryParams();
unset(
    $preservedQuery['sport'],
    $preservedQuery['team'],
    $preservedQuery['gender'],
    $preservedQuery['format'],
);

$buildScopeUrl = static function (
    string $sport,
    string $team,
) use (
    $currentPath,
    $preservedQuery,
): string {
    $query = $preservedQuery;
    $query['sport'] = $sport;
    $query['team'] = $team;

    return $currentPath . '?' . http_build_query($query);
};
$allTeamsUrl = $buildScopeUrl('all', 'all');
?>
<nav class="rh-public-scope-bar" aria-label="Sport and team scope">
    <div class="rh-public-scope-bar-inner"
         data-controller="public-sport-scope"
         data-public-sport-scope-sport-key-value="<?= h($scopeSport) ?>"
         data-public-sport-scope-team-id-value="<?= h($scopeTeam) ?>">
        <div class="dropdown rh-public-scope-dropdown">
            <button class="rh-public-scope-current"
                    type="button"
                    aria-expanded="false"
                    aria-label="Choose sport or team; current selection: <?= h($currentScopeLabel) ?>">
                <?= h($currentScopeLabel) ?>
            </button>
            <button class="rh-public-scope-more-toggle"
                    type="button"
                    aria-expanded="false">
                <span>More</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu rh-public-scope-menu">
                <li class="rh-public-scope-menu-column rh-public-scope-menu-all">
                    <a class="dropdown-item<?= $scopeTeam === 'all' && $scopeSport === 'all' ? ' active' : '' ?>"
                       href="<?= h($allTeamsUrl) ?>"
                       <?= $scopeTeam === 'all' && $scopeSport === 'all' ? 'aria-current="page"' : '' ?>>
                        <?= h($allTeamsLabel) ?>
                    </a>
                </li>

                <?php if ($publicSportOptions !== []) : ?>
                    <li class="rh-public-scope-menu-column">
                        <span class="rh-public-scope-menu-heading">Sports</span>
                        <div class="rh-public-scope-menu-options">
                            <?php foreach ($publicSportOptions as $sportKey => $sportName) : ?>
                                <?php
                                $sportUrl = $buildScopeUrl($sportKey, 'all');
                                $isActiveSport =
                                    $scopeSport === $sportKey
                                    && $scopeTeam === 'all';
                                ?>
                                <a class="dropdown-item<?= $isActiveSport ? ' active' : '' ?>"
                                   href="<?= h($sportUrl) ?>"
                                   <?= $isActiveSport ? 'aria-current="page"' : '' ?>>
                                    <?= h($sportName) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($publicTeamOptions !== []) : ?>
                    <li class="rh-public-scope-menu-column">
                        <span class="rh-public-scope-menu-heading">
                            <?= h($scopeSport !== 'all' && isset($publicSportOptions[$scopeSport])
                                ? $publicSportOptions[$scopeSport] . ' teams'
                                : 'Teams') ?>
                        </span>
                        <div class="rh-public-scope-menu-options">
                            <?php foreach ($publicTeamOptions as $teamId => $teamOption) : ?>
                                <?php
                                $teamUrl = $buildScopeUrl($teamOption['sportKey'], (string)$teamId);
                                $isActiveTeam = $scopeTeam === (string)$teamId;
                                ?>
                                <a class="dropdown-item<?= $isActiveTeam ? ' active' : '' ?>"
                                   href="<?= h($teamUrl) ?>"
                                   <?= $isActiveTeam ? 'aria-current="page"' : '' ?>>
                                    <?= h($teamOption['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
