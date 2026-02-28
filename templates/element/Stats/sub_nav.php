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
    'opponent-player-game' => 'opponentPlayerGame',
];
?>
<?php $this->append('css'); ?>
<style>
.stats-sub-nav{background:var(--rh-navy,#002144);border-radius:.5rem;padding:.25rem;overflow-x:auto;-webkit-overflow-scrolling:touch}
.stats-sub-nav-inner{display:flex;gap:.125rem;min-width:max-content}
.stats-sub-nav-link{color:rgba(255,255,255,.7);font-size:.8125rem;font-weight:500;padding:.375rem .75rem;border-radius:.375rem;white-space:nowrap;text-decoration:none;transition:color .15s,background .15s}
.stats-sub-nav-link:hover{color:#fff;background:rgba(255,255,255,.1);text-decoration:none}
.stats-sub-nav-link.active{color:var(--rh-navy,#002144);background:var(--rh-gold,#ECAC00);font-weight:600}
</style>
<?php $this->end(); ?>
<nav class="stats-sub-nav mb-4" aria-label="Stats navigation">
    <div class="stats-sub-nav-inner" id="stats-sub-nav">
        <a class="stats-sub-nav-link<?= $statType === '' ? ' active' : '' ?>"
           href="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'index']) ?>">
            <i class="bi bi-grid-3x3-gap me-1"></i>All
        </a>
        <?php foreach ($statTypes as $slug => $label) :
            $action = $actionMap[$slug] ?? 'index';
            ?>
            <a class="stats-sub-nav-link<?= $statType === $slug ? ' active' : '' ?>"
               href="<?= $this->Url->build(['controller' => 'Stats', 'action' => $action]) ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
