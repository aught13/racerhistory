<?php
/**
 * Multi-row roster add form.
 *
 * Allows adding multiple roster entries at once. Users can click "+" to add
 * rows and "Save All" to commit them in a single POST via bulkAdd.
 *
 * @var \App\View\AppView $this
 * @var int|null $teamSeasonId Pre-selected team season ID
 * @var \Cake\Collection\CollectionInterface|string[] $teamSeasonsList
 * @var \Cake\Collection\CollectionInterface|string[] $sports
 */
$this->assign('title', 'Add Team Season Roster');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <?php
            if ($teamSeasonId) {
                $backUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            } else {
                $backUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
            }
            ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team
                            Seasons</a></li>
                    <?php if ($teamSeasonId) : ?>
                    <li class="breadcrumb-item"><a href="<?= $backUrl ?>">Team Season View</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">Add Roster</li>
                </ol>
            </nav>
            <h1 class="mb-3">Add Team Season Roster</h1>
        </div>
    </div>

    <turbo-frame id="roster-add-frame">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <?= $this->Form->create(null, [
                'id' => 'bulk-roster-form',
                'url' => ['action' => 'bulkAdd'],
            ]) ?>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Team Season</span>
                </div>
                <div class="card-body">
                    <?= $this->Form->control('team_season_id', [
                        'options' => $teamSeasonsList,
                        'class' => 'form-select',
                        'label' => false,
                        'default' => $teamSeasonId,
                    ]) ?>
                </div>
            </div>

            <div id="roster-rows" data-person-search-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxSearch']) ?>">
                <!-- Initial row rendered server-side -->
                <div class="card mb-2 roster-row" data-row-index="0">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3" style="position:relative">
                                <label class="form-label">Person</label>
                                <input type="text" class="form-control roster-person-search" placeholder="Search persons..." autocomplete="off">
                                <input type="hidden" name="rows[0][person_id]" class="roster-person-id" required>
                                <div class="roster-person-selected small mt-1"><span class="text-muted fst-italic">None selected</span></div>
                                <div class="roster-person-results"></div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Number</label>
                                <input type="text" name="rows[0][roster_number]" class="form-control" maxlength="3">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Position</label>
                                <input type="text" name="rows[0][roster_position]" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Height</label>
                                <input type="text" name="rows[0][roster_height]" class="form-control" maxlength="5" placeholder="6-1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Weight</label>
                                <input type="text" name="rows[0][roster_weight]" class="form-control" maxlength="5">
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row" disabled>
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                <button type="button" id="add-row-btn" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-plus-circle"></i> Add Another
                </button>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                        data-bs-target="#add-person-modal">
                        <i class="bi bi-person-plus"></i> New Person
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <?= $this->Form->button(__('Save All'), [
                    'class' => 'btn btn-primary',
                    'id' => 'save-all-btn',
                ]) ?>
                <a href="<?= $backUrl ?>" class="btn btn-secondary">Cancel</a>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
    </turbo-frame>
</div>

<?php
$personFields = [
    ['name' => 'first_name', 'label' => 'First Name', 'required' => true, 'type' => 'text'],
    ['name' => 'last_name', 'label' => 'Last Name', 'required' => true, 'type' => 'text'],
    ['name' => 'birthdate', 'label' => 'Birthdate', 'type' => 'date'],
    ['name' => 'hometown', 'label' => 'Hometown', 'type' => 'text'],
    ['name' => 'homestate', 'label' => 'Homestate', 'type' => 'text'],
    ['name' => 'homecountry', 'label' => 'Home Country', 'type' => 'text'],
    ['name' => 'sport_id', 'label' => 'Primary Sport', 'type' => 'select', 'options' => $sports, 'required' => true],
];

echo $this->element('Admin/popup_form', [
    'popupId' => 'add-person-modal',
    'title' => 'Add New Person',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd']),
    'targetSelectId' => '',
    'successCallback' => 'onRosterPersonAdded',
    'fields' => $personFields,
    'hiddenFormId' => 'bulk-roster-form',
]);
?>

<?php $this->append('script'); ?>
<script type="module">
import { initRosterMultiAdd } from '/js/modules/roster-multi-add.mjs';

// Bridge: popup_form calls window.onRosterPersonAdded on success.
// We dispatch a custom event so the module picks it up.
window.onRosterPersonAdded = function(data) {
    if (data && data.newOption) {
        document.dispatchEvent(new CustomEvent('popupFormSuccess', {
            detail: { id: data.newOption.value, label: data.newOption.text }
        }));
    }
};

function boot() {
    initRosterMultiAdd();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
document.addEventListener('turbo:load', boot);
</script>
<?php // end script block ?>
<?php $this->end(); ?>
