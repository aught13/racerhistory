<?php $this->assign('title', 'Add Game'); ?>
<div class="container py-4">
    <?php $teamSeasonId = $this->getRequest()->getQuery('team_season_id'); ?>
    <?php if ($teamSeasonId) : ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">
                        Team Seasons
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]) ?>">
                        Team Season
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Add Game</li>
            </ol>
        </nav>
    <?php endif; ?>
    <h1 class="mb-3">Add Game</h1>
    <?= $this->Form->create($game) ?>
    <?= $this->element('Admin/Games/form', compact('game', 'teamSeasonList', 'gameTypes', 'opponents', 'places', 'sites')) ?>
    <?= $this->Form->end() ?>
    <?= $this->element('Admin/Games/form_popups', compact('places')) ?>
</div>


