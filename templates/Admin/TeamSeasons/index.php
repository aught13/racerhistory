<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\TeamSeason> $teamSeasons
 */

$canReadTeamSeasons = $this->Rbac->can('TeamSeasons', 'read');
$canCreateTeamSeasons = $this->Rbac->can('TeamSeasons', 'create');
$canUpdateTeamSeasons = $this->Rbac->can('TeamSeasons', 'update');
$canDeleteTeamSeasons = $this->Rbac->can('TeamSeasons', 'delete');
$canCreateRosters = $this->Rbac->can('TeamSeasonRosters', 'create');
?>
<?php $this->assign('title', 'Manage Team Seasons'); ?>
<div class="container py-4" data-controller="admin-index-table">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Team Seasons Management</h1>
            <p class="text-muted mb-3">
                Manage team participation in specific seasons. Team seasons link teams to seasons and contain detailed
                competition information including league participation, tournament results, and season-specific data.
            </p>
            <?php if ($canCreateTeamSeasons) : ?>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'add']) ?>"
                    class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Team Season
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Team Seasons</h2>
            <?php if (!$teamSeasons->isEmpty()) : ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="team-seasons-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input
                    type="search"
                    id="team-seasons-search"
                    class="form-control form-control-sm"
                    placeholder="Team, season, semester, league..."
                    autocomplete="off"
                    data-admin-index-table-target="searchInput"
                >
            </div>

                <table class="table table-striped table-bordered" id="team-seasons-table" data-admin-index-table-target="table">
                    <thead class="table-dark">
                        <tr>
                            <th>Team</th>
                            <th>Season</th>
                            <th>Semester</th>
                            <th>League</th>
                            <th>League Finish</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamSeasons as $teamSeason) : ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <?= $this->element('team_season_image', ['teamSeason' => $teamSeason, 'size' => 'small', 'deferred' => false]) ?>
                                    </div>
                                    <div>
                                    <?php if (isset($teamSeason->team)) : ?>
                                        <?= h($teamSeason->team->team_name) ?>
                                        <br><small class="text-muted"><?= h($teamSeason->team->abbr) ?></small>
                                    <?php else : ?>
                                        <em>Team not loaded</em>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (isset($teamSeason->season)) : ?>
                                    <?= h($teamSeason->season->start . '-' . $teamSeason->season->end) ?>
                                <?php else : ?>
                                    <em>Season not loaded</em>
                                <?php endif; ?>
                            </td>
                            <td><?= h($teamSeason->semester) ?></td>
                            <td>
                                <?= h($teamSeason->league ?: '-') ?>
                                <?php if ($teamSeason->league_abbr) : ?>
                                    <br><small class="text-muted"><?= h($teamSeason->league_abbr) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($teamSeason->league_finish ?: '-') ?></td>
                            <td>
                                <?php if ($teamSeason->created_at instanceof DateTimeInterface) : ?>
                                    <?= h($teamSeason->created_at->format('M j, Y')) ?>
                                <?php else : ?>
                                    <?= h($teamSeason->created_at) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canReadTeamSeasons) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeason->id]) ?>"
                                        class="btn btn-sm btn-info">View</a>
                                <?php endif; ?>
                                <?php if ($canUpdateTeamSeasons) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'edit', $teamSeason->id]) ?>"
                                        class="btn btn-sm btn-primary">Edit</a>
                                <?php endif; ?>
                                <?php if ($canCreateRosters) : ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasonRosters', 'action' => 'add', '?' => ['team_season_id' => $teamSeason->id]]) ?>"
                                        class="btn btn-sm btn-success">Add Roster</a>
                                <?php endif; ?>
                                <?php if ($canDeleteTeamSeasons) : ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirm-delete-modal"
                                        data-delete-url="<?= $this->Url->build([
                                            'prefix' => 'Admin',
                                            'controller' => 'TeamSeasons',
                                            'action' => 'delete',
                                            $teamSeason->id,
                                        ]) ?>"
                                        data-item-type="team season"
                                        data-item-name="<?= h($teamSeason->team->team_name . ' (' . $teamSeason->season->start . '-' . $teamSeason->season->end . ')') ?>"
                                        aria-label="Delete team season for <?= h($teamSeason->team->team_name) ?>">Delete</button>
                                <?php endif; ?>
                                <?php if (!$canReadTeamSeasons && !$canUpdateTeamSeasons && !$canCreateRosters && !$canDeleteTeamSeasons) : ?>
                                    <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
            <div class="alert alert-info">No team seasons have been created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team season']) ?>
