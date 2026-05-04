<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $eavTemplate
 * @var mixed $legacyMappedEav
 * @var mixed $sportHasStats
 * @var mixed $sportId
 * @var mixed $sportName
 * @var \App\Model\Entity\Game $game
 */
?>
<?php $this->assign('title', 'Add Results'); ?>
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
                <li class="breadcrumb-item">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'view', $game->id]) ?>">
                        Game
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Add Results</li>
            </ol>
        </nav>
    <?php endif; ?>
    <h1 class="mb-3">Add Results</h1>

    <?php
    // Merge legacy mapped EAV for form rendering if provided
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
    <?= $this->element('Admin/Games/form_results', compact('game', 'eav', 'eavTemplate', 'sportId', 'sportName', 'sportHasStats')) ?>
    <?= $this->Form->end() ?>
</div>
