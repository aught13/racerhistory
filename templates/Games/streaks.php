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

    <?php
        // Separate active streaks from ended ones
        $activeStreaks = array_filter($streaks, function ($s) { return !empty($s['active']); });
        $endedStreaks = array_filter($streaks, function ($s) { return empty($s['active']); });
    ?>

    <div class="card mb-4">
        <div class="card-body">
            <?php if (!empty($activeStreaks)) : ?>
                <?php foreach ($activeStreaks as $streak) : ?>
                    <?php
                        $parts = explode('-', trim($streak['start_date']));
                        $startDate = count($parts) === 3 ? sprintf('%s/%s/%s', $parts[1], $parts[2], $parts[0]) : $streak['start_date'];
                    ?>
                    Current: <?= (int)$streak['length'] ?> <?= $resultType === 'W' ? 'wins' : 'losses' ?> in a row. Started: <?= h($startDate) ?> <?= h($streak['start_opponent']) ?>
                <?php endforeach; ?>
            <?php else : ?>
                Current: None
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($endedStreaks)) : ?>
        <h3 class="h5 mb-3">Top 20 <?= $resultType === 'W' ? 'Winning' : 'Losing' ?> Streaks</h3>
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
                                <th>Ended with <?= $resultType === 'W' ? 'Loss vs' : 'Win vs' ?></th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($endedStreaks as $streak) : ?>
                                <tr>
                                    <td><?= $streak['rank'] ?? '' ?></td>
                                    <td><strong><?= (int)$streak['length'] ?></strong></td>
                                    <td><?php
                                        $parts = explode('-', trim($streak['start_date']));
                                        if (count($parts) === 3) {
                                            echo h(sprintf('%s/%s/%s', $parts[1], $parts[2], $parts[0]));
                                        } else {
                                            echo h($streak['start_date']);
                                        }
                                    ?></td>
                                    <td><?php
                                        $parts = explode('-', trim($streak['end_date']));
                                        if (count($parts) === 3) {
                                            echo h(sprintf('%s/%s/%s', $parts[1], $parts[2], $parts[0]));
                                        } else {
                                            echo h($streak['end_date']);
                                        }
                                    ?></td>
                                    <td><?= h($streak['start_opponent']) ?></td>
                                    <td><?= h($streak['end_opponent']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">Ended</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (empty($activeStreaks) && empty($endedStreaks)) : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No streaks found with the selected filters.
        </div>
    <?php endif; ?>
</div>
