<?php
/**
 * RacerHistory Home Page
 *
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Home');
?>

<div class="rh-home-content">
    <!-- Inject the new WordPress-style Hero + 3-Grid Array -->
    <?= $this->cell('BlogWidget::homeFeed') ?>

    <?= $this->element('Ads/block', ['slot' => 'homepage_mid']) ?>

    <hr class="my-3" />

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="bi bi-trophy-fill text-warning"></i>
                        Seasons
                    </h2>
                    <p class="card-text">
                        Browse through decades of Murray State Men's Basketball seasons. View rosters, game results, and season statistics.
                    </p>
                    <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'index']) ?>" class="btn btn-primary">
                        View Seasons
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="bi bi-people-fill text-info"></i>
                        People
                    </h2>
                    <p class="card-text">
                        Explore profiles of players, coaches, and staff who have been part of Murray State Basketball history.
                    </p>
                    <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'index']) ?>" class="btn btn-primary">
                        View People
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="bi bi-bar-chart-fill text-success"></i>
                        Statistics
                    </h2>
                    <p class="card-text">
                        Dive deep into player and team statistics. Compare seasons, analyze performance, and discover record holders.
                    </p>
                    <a href="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'index']) ?>" class="btn btn-primary">
                        View Stats
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="bi bi-calendar-event-fill text-danger"></i>
                        Games
                    </h2>
                    <p class="card-text">
                        Review game results, box scores, and memorable moments from Murray State Basketball games throughout history.
                    </p>
                    <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>" class="btn btn-primary">
                        View Games
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3 class="card-title">Welcome to RacerHistory</h3>
                    <p class="card-text lead">
                        Your comprehensive source for Murray State Men's Basketball history.
                        Explore decades of tradition, excellence, and Racer pride.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rh-home-content {
    padding-top: 0;
}

.rh-home-content .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--rh-border);
}

.rh-home-content .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.rh-home-content .card-title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rh-home-content .card-title i {
    font-size: 1.75rem;
}

.rh-home-content .btn {
    margin-top: 1rem;
}
</style>
