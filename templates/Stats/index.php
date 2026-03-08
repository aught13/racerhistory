<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $statTypes
 * @var string $currentSport
 */
$this->assign('title', 'Statistics');
?>
<div class="container py-4">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Statistics</h1>
        <p class="text-muted mb-0">Search and explore statistics</p>
    </div>

    <div class="row g-3" id="stats-type-cards">
        <?php foreach ($statTypes as $slug => $label) : ?>
            <div class="col-md-4 col-lg-4">
                <a href="<?= $this->Url->build(['controller' => 'Stats', 'action' => \Cake\Utility\Inflector::variable(str_replace('-', '_', $slug))]) ?>"
                   class="card h-100 text-decoration-none stat-type-card">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= h($label) ?></h5>
                        <p class="card-text text-muted small">View <?= h(strtolower($label)) ?> statistics</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php $statsInitVer = @filemtime(WWW_ROOT . 'js' . DS . 'stats-init-loader.mjs') ?: 0; ?>
<script type="module" src="/js/stats-init-loader.mjs?v=<?= $statsInitVer ?>"></script>
