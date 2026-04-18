<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeasonRoster $teamSeasonRoster
 */
$this->assign('title', 'View Team Season Roster');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team
                            Seasons</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonRoster->team_season_id]) ?>">Team
                            Season View</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Roster</li>
                </ol>
            </nav>
            <h1 class="mb-3">Roster Details</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <?= h($teamSeasonRoster->person->display) ?>
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Team Season</dt>
                        <dd class="col-sm-9"><a
                                href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonRoster->team_season_id]) ?>">
                                <?= h($teamSeasonRoster->team_season->team->team_name) ?>
                                (<?= h($teamSeasonRoster->team_season->season->start . '-' . $teamSeasonRoster->team_season->season->end) ?>)
                            </a></dd>

                        <dt class="col-sm-3">Person</dt>
                        <dd class="col-sm-9"><a
                                href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'view', $teamSeasonRoster->person_id]) ?>">
                                <?= h($teamSeasonRoster->person->display) ?>
                            </a></dd>

                        <dt class="col-sm-3">Year</dt>
                        <dd class="col-sm-9"><?= h($teamSeasonRoster->roster_year) ?></dd>

                        <dt class="col-sm-3">Number</dt>
                        <dd class="col-sm-9"><?= h($teamSeasonRoster->roster_number) ?></dd>

                        <dt class="col-sm-3">Position</dt>
                        <dd class="col-sm-9"><?= h($teamSeasonRoster->roster_position) ?></dd>

                        <dt class="col-sm-3">Height</dt>
                        <dd class="col-sm-9"><?= h($teamSeasonRoster->roster_height) ?></dd>

                        <dt class="col-sm-3">Weight</dt>
                        <dd class="col-sm-9"><?= h($teamSeasonRoster->roster_weight) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'edit', $teamSeasonRoster->id]) ?>"
                        class="btn btn-primary d-block mb-2">Edit Roster Entry</a>
                    <?= $this->Form->postLink(__('Delete Roster Entry'), ['action' => 'delete', $teamSeasonRoster->id], ['confirm' => __('Are you sure you want to delete this roster entry?'), 'class' => 'btn btn-danger d-block']) ?>
                </div>
            </div>
        </div>
    </div>
</div>
