<?php
declare(strict_types=1);
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $searchTypes
 * @var string $currentSearch
 * @var array $games
 * @var string $type
 * @var string $filter
 */
$this->assign('title', ($type === 'win' ? 'Biggest Wins' : 'Largest Losses'));

$filterLabels = [
    'overall' => 'Overall',
    'home' => 'Home',
    'road' => 'Road',
    'neutral' => 'Neutral',
    'conf' => 'Conf Overall',
    'conf_home' => 'Conf Home',
    'conf_road' => 'Conf Road',
];
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a></li>
            <li class="breadcrumb-item active" aria-current="page">Margins</li>
        </ol>
    </nav>

    <?= $this->element('Games/sub_nav', ['searchTypes' => $searchTypes, 'currentSearch' => $currentSearch]) ?>

    <h1 class="h3 mb-3"><?= $type === 'win' ? 'Biggest Wins' : 'Largest Losses' ?></h1>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <div class="btn-group" role="group" aria-label="Margin type">
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'margins', '?' => ['type' => 'win', 'filter' => $filter]]) ?>"
               class="btn btn-sm <?= $type === 'win' ? 'btn-success' : 'btn-outline-success' ?>">
                Biggest Wins
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'margins', '?' => ['type' => 'loss', 'filter' => $filter]]) ?>"
               class="btn btn-sm <?= $type === 'loss' ? 'btn-danger' : 'btn-outline-danger' ?>">
                Largest Losses
            </a>
        </div>
    </div>

    <div class="btn-group mb-4" role="group" aria-label="Filter">
        <?php foreach ($filterLabels as $key => $label) : ?>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'margins', '?' => ['type' => $type, 'filter' => $key]]) ?>"
               class="btn btn-sm <?= $filter === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($games)) : ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Margin</th>
                                <th>Date</th>
                                <th>Opponent</th>
                                <th>Score</th>
                                <th>H/R/N</th>
                                <th>OT</th>
                                <th>Pts For</th>
                                <th>Pts Against</th>
                                <th>Season</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($games as $i => $game) : ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= (int)($game->margin ?? 0) ?></strong></td>
                                    <td><?= h((string)($game->game_date ?? '-')) ?></td>
                                    <td>
                                        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">
                                            <?= h($game->opponent->opponent_name ?? '?') ?>
                                        </a>
                                    </td>
                                    <td><?= h($game->pts_mur ?? '-') ?>-<?= h($game->pts_opp ?? '-') ?></td>
                                    <td><?= \App\Service\GameSearchService::hrnLabel((int)($game->hrn ?? 0)) ?></td>
                                    <td><?= h((string)($game->ot ?? '')) ?></td>
                                    <td><?= (int)($game->pts_mur ?? 0) ?></td>
                                    <td><?= (int)($game->pts_opp ?? 0) ?></td>
                                    <td>
                                        <?= h(($game->team_season->season->start ?? '') . '-' . ($game->team_season->season->end ?? '')) ?>
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
            No games found with the selected filters.
        </div>
    <?php endif; ?>
</div>
