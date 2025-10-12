<?php $this->assign('title', 'Edit Game'); ?>
<div class="container py-4">
    <?php if (isset($game) && $game->team_season_id) : ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">
                        Team Seasons
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $game->team_season_id]) ?>">
                        Team Season
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Edit Game</li>
            </ol>
        </nav>
    <?php endif; ?>
    <h1 class="mb-3">Edit Game</h1>
    <?php
    // Merge legacy mapped EAV (period_X_team/opponent) into eav for form rendering if provided
    if (isset($legacyMappedEav) && is_array($legacyMappedEav)) {
        if (!isset($eav) || !is_array($eav)) {
            $eav = [];
        }
        // Do not overwrite existing explicit $eav values; only fill gaps.
        foreach ($legacyMappedEav as $k => $v) {
            if (!array_key_exists($k, $eav)) {
                $eav[$k] = $v;
            }
        }
    }
    ?>
    <?= $this->Form->create($game) ?>
    <?= $this->element('Admin/Games/form', compact('game', 'teamSeasonList', 'gameTypes', 'opponents', 'places', 'sites', 'eav')) ?>
    <?= $this->Form->end() ?>
</div>


