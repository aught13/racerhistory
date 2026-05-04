<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $eavTemplate
 * @var mixed $gameTypes
 * @var mixed $legacyMappedEav
 * @var mixed $lookupDisplays
 * @var mixed $opponents
 * @var mixed $places
 * @var mixed $sites
 * @var mixed $sportHasStats
 * @var mixed $sportId
 * @var mixed $sportName
 * @var mixed $teamSeasonList
 * @var \App\Model\Entity\Game $game
 */
?>
<?php $this->assign('title', 'Edit Game'); ?>
<?php
// Determine if this is a past game (game_date is today or before)
$isPastGame = isset($game) && $game->game_date && $game->game_date->isPast();
// Determine the edit mode from query param: 'details' or 'results'
$editMode = $this->getRequest()->getQuery('mode', $isPastGame ? 'results' : 'details');
?>
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

    <?php if (isset($game) && $game->id) : ?>
        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Edit mode toggle">
                <a href="<?= $this->Url->build(['action' => 'edit', $game->id, '?' => ['mode' => 'details']]) ?>"
                   class="btn btn-<?= $editMode === 'details' ? 'primary' : 'outline-primary' ?>">
                    <i class="bi bi-calendar-event"></i> Game Details
                </a>
                <a href="<?= $this->Url->build(['action' => 'edit', $game->id, '?' => ['mode' => 'results']]) ?>"
                   class="btn btn-<?= $editMode === 'results' ? 'primary' : 'outline-primary' ?>">
                    <i class="bi bi-trophy"></i> Game Results
                </a>
            </div>
            <?php if (!empty($sportHasStats)) : ?>
                <a href="<?= $this->Url->build(['controller' => 'StatBasketGameBox', 'action' => 'gameBox', $game->id]) ?>"
                   class="btn btn-success ms-2">
                    <i class="bi bi-clipboard-data"></i>
                    Add/Edit Game Box Scores
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php
    // Merge legacy mapped EAV (period_X_team/opponent) into eav for form rendering if provided
    if (isset($legacyMappedEav) && is_array($legacyMappedEav)) {
        if (!isset($eav) || !is_array($eav)) {
            $eav = [];
        }
        foreach ($legacyMappedEav as $k => $v) {
            if (!array_key_exists($k, $eav)) {
                $eav[$k] = $v;
            }
        }
    }
    ?>
    <?= $this->Form->create($game) ?>
    <?php if ($editMode === 'results') : ?>
        <?= $this->element('Admin/Games/form_results', compact('game', 'eav', 'eavTemplate', 'sportId', 'sportName', 'sportHasStats')) ?>
    <?php else : ?>
        <?= $this->element('Admin/Games/form_details', compact('game', 'teamSeasonList', 'gameTypes', 'opponents', 'places', 'sites', 'lookupDisplays')) ?>
    <?php endif; ?>
    <?= $this->Form->end() ?>
    <?php if ($editMode === 'details') : ?>
        <?= $this->element('Admin/Games/form_popups', compact('places')) ?>
    <?php endif; ?>
</div>



