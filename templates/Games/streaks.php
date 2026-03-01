<?php
declare(strict_types=1);
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var array $streaks
 * @var string $resultType
 * @var string $filter
 */
$this->assign('title', ($resultType === 'W' ? 'Winning' : 'Losing') . ' Streaks');

$filterLabels = [
    'overall' => 'Overall',
    'home' => 'Home',
    'road' => 'Road',
    'conf' => 'Conf Overall',
    'conf_home' => 'Conf Home',
    'conf_road' => 'Conf Road',
];
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a></li>
            <li class="breadcrumb-item active" aria-current="page">Streaks</li>
        </ol>
    </nav>

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-3"><?= $resultType === 'W' ? 'Winning' : 'Losing' ?> Streaks</h1>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <div class="btn-group" role="group" aria-label="Result type">
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'streaks', '?' => ['result' => 'W', 'filter' => $filter]]) ?>"
               class="btn btn-sm <?= $resultType === 'W' ? 'btn-success' : 'btn-outline-success' ?>">
                Winning
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'streaks', '?' => ['result' => 'L', 'filter' => $filter]]) ?>"
               class="btn btn-sm <?= $resultType === 'L' ? 'btn-danger' : 'btn-outline-danger' ?>">
                Losing
            </a>
        </div>
    </div>

    <div class="btn-group mb-4" role="group" aria-label="Filter">
        <?php foreach ($filterLabels as $key => $label) : ?>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'streaks', '?' => ['result' => $resultType, 'filter' => $key]]) ?>"
               class="btn btn-sm <?= $filter === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($streaks)) : ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Length</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Started vs</th>
                                <th>Ended vs</th>
                                <th>Season</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($streaks as $i => $streak) : ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= (int)$streak['length'] ?></strong></td>
                                    <td><?= h($streak['start_date']) ?></td>
                                    <td><?= h($streak['end_date']) ?></td>
                                    <td><?= h($streak['start_opponent']) ?></td>
                                    <td><?= h($streak['end_opponent']) ?></td>
                                    <td><?= h($streak['season'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($streak['active'])) : ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary">Ended</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No streaks found with the selected filters.
        </div>
    <?php endif; ?>
</div>
