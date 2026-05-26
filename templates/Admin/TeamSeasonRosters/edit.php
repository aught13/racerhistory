<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeasonRoster $teamSeasonRoster
 * @var \Cake\Collection\CollectionInterface|array<string> $teamSeasonsList
 * @var \Cake\Collection\CollectionInterface|array<string> $persons
 */
$this->assign('title', 'Edit Team Season Roster');
$currentPersonId = isset($teamSeasonRoster->person_id) ? (int)$teamSeasonRoster->person_id : 0;
$currentPersonLabel = isset($teamSeasonRoster->person) ? (string)$teamSeasonRoster->person->display : '';
?>
<div class="container py-4" data-controller="roster-edit-person" data-roster-edit-person-search-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxSearch'])) ?>" data-roster-edit-person-current-id-value="<?= $currentPersonId ?>" data-roster-edit-person-current-label-value="<?= h($currentPersonLabel) ?>">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons',
                            'action' => 'index']) ?>">Team Seasons</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons',
                            'action' => 'view', $teamSeasonRoster->team_season_id]) ?>">Team
                            Season View</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Roster</li>
                </ol>
            </nav>
            <h1 class="mb-3">Edit Team Season Roster</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <?= $this->Form->create($teamSeasonRoster, ['id' => 'main-roster-form']) ?>
                    <fieldset>
                        <?php
                        echo $this->Form->control('team_season_id', [
                            'options' => $teamSeasonsList,
                            'class' => 'form-select',
                            'label' => 'Team Season',
                        ]);
                        echo $this->Form->control('person_id', [
                            'empty' => '(Start typing to search people...)',
                            'options' => [],
                            'class' => 'form-select',
                            'label' => 'Person',
                            'id' => 'person-id-select',
                            'data-dynamic-person' => '1',
                            'data-roster-edit-person-target' => 'select',
                        ]);
                        echo '<small class="text-muted">
                        Type at least 2 characters to search by display / first / last name.</small>';
                        ?>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#add-person-modal">
                                <i class="bi bi-plus-circle"></i> Add New Person
                            </button>
                        </div>
                        <?php
                        echo $this->Form->control('roster_year', ['class' => 'form-control', 'label' => 'Year']);
                        echo $this->Form->control('roster_number', ['class' => 'form-control', 'label' => 'Number']);
                        echo $this->Form->control('roster_position', ['class' => 'form-control',
                        'label' => 'Position']);
                        echo $this->Form->control('roster_height', ['class' => 'form-control', 'label' => 'Height']);
                        echo $this->Form->control('roster_weight', ['class' => 'form-control', 'label' => 'Weight']);
                        ?>
                    </fieldset>
                    <div class="mt-3">
                        <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons',
                        'action' => 'view', $teamSeasonRoster->team_season_id]) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
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
    'targetSelectId' => 'person-id-select',
    'fields' => $personFields,
    'hiddenFormId' => 'hidden-person-form',
    'extraHtml' => '<div class="mb-3" data-controller="place-search" data-place-search-search-url-value="' . h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch'])) . '"><label class="form-label">Birth Place</label><div class="input-group"><input type="text" id="add-person-modal-birth-place-search" class="form-control" placeholder="Search places..." autocomplete="off" data-place-search-target="input" data-action="input->place-search#search"><button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#add-roster-edit-birth-place-modal" title="Add New Place"><i class="bi bi-plus-circle"></i> New</button></div><input type="hidden" id="add-person-modal-birth-place-id" name="birth_place_id" data-place-search-target="hidden"><div id="add-person-modal-birth-place-results" class="mt-1" data-place-search-target="results"></div><div id="add-person-modal-birth-place-selected" class="small mt-1" data-place-search-target="selected"><span class="text-muted fst-italic">None selected</span></div></div>',
]);
?>

<!-- Hidden form for FormProtection tokens (place ajaxAdd endpoint) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd'],
        'id' => 'hidden-roster-edit-place-form',
    ]) ?>
    <?= $this->Form->control('place_country', ['type' => 'text']) ?>
    <?= $this->Form->control('place_city', ['type' => 'text']) ?>
    <?= $this->Form->control('place_state', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-roster-edit-birth-place-modal',
    'title' => 'Add New Place',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd']),
    'hiddenFormId' => 'hidden-roster-edit-place-form',
    'successCallback' => 'handleBirthPlaceAdded',
    'fields' => [
        ['name' => 'place_country', 'type' => 'text', 'label' => 'Country (ISO 3166 alpha-3)', 'required' => true],
        ['name' => 'place_city', 'type' => 'text', 'label' => 'Locality (city, town, or village)', 'required' => true],
        ['name' => 'place_state', 'type' => 'text', 'label' => 'Subdivision (state, province, or region)'],
    ],
]) ?>
