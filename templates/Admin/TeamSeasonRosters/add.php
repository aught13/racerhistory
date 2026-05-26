<?php
/**
 * Multi-row roster add form.
 *
 * Allows adding multiple roster entries at once. Users can click "+" to add
 * rows and "Save All" to commit them in a single POST via bulkAdd.
 *
 * @var \App\View\AppView $this
 * @var int|null $teamSeasonId Pre-selected team season ID
 * @var \Cake\Collection\CollectionInterface|array<string> $teamSeasonsList
 * @var \Cake\Collection\CollectionInterface|array<string> $sports
 */
$this->assign('title', 'Add Team Season Roster');
?>
<div class="container py-4" data-controller="roster-multi-add">
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

    <turbo-frame id="roster-add-frame" target="_top">
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

            <div id="roster-rows" data-roster-multi-add-target="rows" data-person-search-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxSearch']) ?>">
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
                            <div class="col-md-1">
                                <label class="form-label">Year</label>
                                <input type="text" name="rows[0][roster_year]" class="form-control" maxlength="10" placeholder="Sr.">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Number</label>
                                <input type="text" name="rows[0][roster_number]" class="form-control" maxlength="3">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Position</label>
                                <input type="text" name="rows[0][roster_position]" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-1">
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
                <button type="button" id="add-row-btn" data-roster-multi-add-target="addButton" class="btn btn-outline-success btn-sm">
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
    ['name' => 'first', 'label' => 'First Name', 'type' => 'text'],
    ['name' => 'last', 'label' => 'Last Name', 'type' => 'text'],
    ['name' => 'full', 'label' => 'Full Name', 'type' => 'text'],
    ['name' => 'display', 'label' => 'Display Name', 'required' => true, 'type' => 'text'],
    ['name' => 'birth', 'label' => 'Birth Date', 'type' => 'date'],
    ['name' => 'death', 'label' => 'Death Date', 'type' => 'date'],
    ['name' => 'person_previous', 'label' => 'Previous School', 'type' => 'text'],
];
?>

<!-- Hidden form for FormProtection tokens (person ajaxAdd endpoint) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd'],
        'id' => 'hidden-person-form',
    ]) ?>
    <?= $this->Form->control('first', ['type' => 'text']) ?>
    <?= $this->Form->control('last', ['type' => 'text']) ?>
    <?= $this->Form->control('full', ['type' => 'text']) ?>
    <?= $this->Form->control('display', ['type' => 'text']) ?>
    <?= $this->Form->control('birth', ['type' => 'text']) ?>
    <?= $this->Form->control('death', ['type' => 'text']) ?>
    <?= $this->Form->control('person_previous', ['type' => 'text']) ?>
    <?= $this->Form->control('birth_place_id', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?php
echo $this->element('Admin/popup_form', [
    'popupId' => 'add-person-modal',
    'title' => 'Add New Person',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd']),
    'targetSelectId' => '',
    'successCallback' => 'onRosterPersonAdded',
    'fields' => $personFields,
    'hiddenFormId' => 'hidden-person-form',
    'extraHtml' => '<div class="mb-3" data-controller="place-search" data-place-search-search-url-value="' . h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch'])) . '"><label class="form-label">Birth Place</label><div class="input-group"><input type="text" id="add-person-modal-birth-place-search" class="form-control" placeholder="Search places..." autocomplete="off" data-place-search-target="input" data-action="input->place-search#search"><button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#add-roster-birth-place-modal" title="Add New Place"><i class="bi bi-plus-circle"></i> New</button></div><input type="hidden" id="add-person-modal-birth-place-id" name="birth_place_id" data-place-search-target="hidden"><div id="add-person-modal-birth-place-results" class="mt-1" data-place-search-target="results"></div><div id="add-person-modal-birth-place-selected" class="small mt-1" data-place-search-target="selected"><span class="text-muted fst-italic">None selected</span></div></div>',
]);
?>

<!-- Hidden form for FormProtection tokens (place ajaxAdd endpoint) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd'],
        'id' => 'hidden-roster-place-form',
    ]) ?>
    <?= $this->Form->control('place_country', ['type' => 'text']) ?>
    <?= $this->Form->control('place_city', ['type' => 'text']) ?>
    <?= $this->Form->control('place_state', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-roster-birth-place-modal',
    'title' => 'Add New Place',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd']),
    'hiddenFormId' => 'hidden-roster-place-form',
    'successCallback' => 'handleBirthPlaceAdded',
    'fields' => [
        ['name' => 'place_country', 'type' => 'text', 'label' => 'Country (ISO 3166 alpha-3)', 'required' => true],
        ['name' => 'place_city', 'type' => 'text', 'label' => 'Locality (city, town, or village)', 'required' => true],
        ['name' => 'place_state', 'type' => 'text', 'label' => 'Subdivision (state, province, or region)'],
    ],
]) ?>
