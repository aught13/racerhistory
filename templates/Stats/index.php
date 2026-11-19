<?php
declare(strict_types=1);

use Cake\Utility\Inflector;

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $statTypes
 * @var string|null $currentSport
 * @var array<string,string> $supportedSports
 * @var bool $statsScopeUnavailable
 */
$this->assign('title', 'Statistics');
$statsScopeUnavailable = $statsScopeUnavailable ?? false;
?>
<div class="container py-4" data-controller="stats-page">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Statistics</h1>
        <p class="text-muted mb-0">Search and explore statistics</p>
    </div>

    <?php if ($currentSport === null) : ?>
        <?php if ($statsScopeUnavailable) : ?>
            <div class="rh-stats-selection-note border border-secondary-subtle rounded px-3 py-4 text-secondary text-center" aria-disabled="true">
                <i class="bi bi-bar-chart fs-2 mb-2 d-block" aria-hidden="true"></i>
                <strong>No stats are available for the selected sport or team</strong>
            </div>
        <?php elseif (count($supportedSports) > 1) : ?>
            <div class="rh-stats-selection-note border border-secondary-subtle rounded px-3 py-4 text-secondary text-center" aria-disabled="true">
                <i class="bi bi-bar-chart fs-2 mb-2 d-block" aria-hidden="true"></i>
                <strong>Select a team or sport for stats</strong>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="row g-3" id="stats-type-cards">
            <?php foreach ($statTypes as $slug => $label) : ?>
                <div class="col-md-4 col-lg-4">
                    <a href="<?= $this->PublicScope->url([
                        'controller' => 'Stats',
                        'action' => Inflector::variable(str_replace('-', '_', $slug)),
                    ]) ?>"
                       class="card h-100 text-decoration-none stat-type-card">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= h($label) ?></h5>
                            <p class="card-text text-muted small">View <?= h(strtolower($label)) ?> statistics</p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
