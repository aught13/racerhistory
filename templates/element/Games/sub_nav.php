<?php
declare(strict_types=1);

/**
 * Games sub-navigation element.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
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
?>
<?php $this->append('css'); ?>
<style>
.games-sub-nav{background:var(--rh-navy,#002144);border-radius:.5rem;padding:.25rem;overflow-x:auto;-webkit-overflow-scrolling:touch}
.games-sub-nav-inner{display:flex;gap:.125rem;min-width:max-content}
.games-sub-nav-link{color:rgba(255,255,255,.7);font-size:.8125rem;font-weight:500;padding:.375rem .75rem;border-radius:.375rem;white-space:nowrap;text-decoration:none;transition:color .15s,background .15s}
.games-sub-nav-link:hover{color:#fff;background:rgba(255,255,255,.1);text-decoration:none}
.games-sub-nav-link.active{color:var(--rh-navy,#002144);background:var(--rh-gold,#ECAC00);font-weight:600}
</style>
<?php $this->end(); ?>
<nav class="games-sub-nav mb-4" aria-label="Games navigation">
    <div class="games-sub-nav-inner" id="games-sub-nav">
        <a class="games-sub-nav-link<?= $currentSearch === '' ? ' active' : '' ?>"
           href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">
            <i class="bi bi-grid-3x3-gap me-1"></i>All
        </a>
        <?php foreach ($searchTypes as $slug => $label) :
            $action = $actionMap[$slug] ?? 'index';
            ?>
            <a class="games-sub-nav-link<?= $currentSearch === $slug ? ' active' : '' ?>"
               href="<?= $this->Url->build(['controller' => 'Games', 'action' => $action]) ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
