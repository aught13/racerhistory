<?php
declare(strict_types=1);

use Cake\Utility\Inflector;

/**
 * @var \App\View\AppView $this
 * @var array<string, string> $statTypes
 * @var string|null $currentSport
 * @var array<string,string> $supportedSports
 */
$this->assign('title', 'Statistics');
?>
<div class="container py-4" data-controller="stats-page">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Statistics</h1>
        <p class="text-muted mb-0">Search and explore statistics</p>
    </div>

    <?php if ($currentSport === null) : ?>
        <form method="get" action="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'index']) ?>" class="row g-2 align-items-end mb-4">
            <div class="col-sm-6 col-md-4">
                <label class="form-label" for="stats-sport">Sport</label>
                <select id="stats-sport" class="form-select" name="sport" required>
                    <option value="" selected disabled>Choose a sport</option>
                    <?php foreach ($supportedSports as $sportKey => $sportName) : ?>
                        <option value="<?= h($sportKey) ?>"><?= h($sportName) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Choose a sport to view its available statistics.</div>
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-primary">View statistics</button>
            </div>
        </form>
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
