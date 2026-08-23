<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */

$canUpdateTeams = $this->Rbac->can('Teams', 'update');
$canDeleteTeams = $this->Rbac->can('Teams', 'delete');
?>
<?php $this->assign('title', 'Team Details'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>">Teams</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Team Details</li>
                </ol>
            </nav>
            <h1 class="mb-3">Team Details</h1>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                class="btn btn-secondary mb-3">Back to Teams</a>
            <?php if ($canUpdateTeams) : ?>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                    class="btn btn-primary mb-3">Edit Team</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0"><?= h($team->team_name) ?></h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Sport Category:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->sport ? $team->sport->sport_name : 'N/A') ?>
                            <br><small class="text-muted">Competition category for this team</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Team Name:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->team_name) ?>
                            <br><small class="text-muted">Short display name (max 162 characters)</small>
                        </div>
                    </div>

                    <?php if (!empty($team->team_description)) : ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Long Name:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->team_description) ?>
                            <br><small class="text-muted">Full official name including institution and sport (max 240 characters)</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Abbreviation:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?= h($team->abbr) ?>
                            <br><small class="text-muted">Short code for compact display (max 5 characters)</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Gender Classification:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php
                            $genderLabels = ['M' => 'Male', 'F' => 'Female', 'C' => 'Co-ed'];
                            $genderLabel = $genderLabels[$team->gender] ?? $team->gender;
                            ?>
                            <?= h($genderLabel) ?>
                            <br><small class="text-muted">Competition gender category</small>
                        </div>
                    </div>

                    <?php if ($team->created_at) : ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Created:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php
                            $created = $team->created_at;
                            if ($created instanceof DateTimeInterface) {
                                echo h($created->format('M j, Y g:i A'));
                            } else {
                                echo h($created);
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($team->updated_at) : ?>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Last Updated:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php
                            $updated = $team->updated_at;
                            if ($updated instanceof DateTimeInterface) {
                                echo h($updated->format('M j, Y g:i A'));
                            } else {
                                echo h($updated);
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <?php if ($canUpdateTeams) : ?>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                               class="btn btn-primary">Edit Team</a>
                        <?php endif; ?>
                        <?php
                            // Placeholder for future associations (e.g., events, seasons, athletes)
                            $associatedData = [
                                ['label' => 'Related Records', 'count' => 0],
                            ];
                            ?>
                        <?php if ($canDeleteTeams) : ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'delete', $team->id]) ?>"
                                data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'edit', $team->id]) ?>"
                                data-item-type="team"
                                data-associated='<?= json_encode($associatedData) ?>'>
                                Delete Team
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Sport:</strong> <?= h($team->sport ? $team->sport->sport_name : 'N/A') ?></p>
                    <p><strong>Gender:</strong> <?= h($genderLabel) ?></p>
                    <p><strong>Abbreviation:</strong> <?= h($team->abbr) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'team']) ?>
