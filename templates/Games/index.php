<?php
declare(strict_types=1);

use Cake\Utility\Inflector;

/**
 * Games landing page with search type cards.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 */
$this->assign('title', 'Games');

$icons = [
    'ranked' => 'bi-trophy',
    'overtime' => 'bi-clock-history',
    'hundred-point' => 'bi-fire',
    'openers' => 'bi-calendar-event',
    'streaks' => 'bi-lightning',
    'margins' => 'bi-bar-chart',
    'series' => 'bi-people',
];

$descriptions = [
    'ranked' => 'Games involving ranked teams',
    'overtime' => 'Games that went to overtime',
    'hundred-point' => 'Games with 100+ points scored',
    'openers' => 'Season, home, and conference openers',
    'streaks' => 'Longest winning and losing streaks',
    'margins' => 'Biggest wins and worst losses',
    'series' => 'Head-to-head records vs opponents',
];
?>
<div class="container py-4" data-controller="games-search">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Games</h1>
        <p class="text-muted mb-0">Explore Men's Basketball game history</p>
    </div>

    <div class="row g-3" id="games-type-cards">
        <?php foreach ($searchTypes as $slug => $label) : ?>
            <div class="col-md-4 col-lg-3">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => Inflector::variable(str_replace('-', '_', $slug))]) ?>"
                   class="card h-100 text-decoration-none game-type-card">
                    <div class="card-body text-center">
                        <i class="bi <?= $icons[$slug] ?? 'bi-list' ?> fs-2 mb-2 d-block text-primary"></i>
                        <h5 class="card-title"><?= h($label) ?></h5>
                        <p class="card-text text-muted small"><?= h($descriptions[$slug] ?? '') ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
