<?php
// Popup forms and hidden FormProtection token forms for Games add/edit.
// MUST be rendered OUTSIDE the main game Form->create() / Form->end() block.
// Expects: $opponents (unused), $places, $sites (unused),
//          $opponentAjaxAddUrl, $placeAjaxAddUrl, $siteAjaxAddUrl, $gameTypeAjaxAddUrl

$opponentAjaxAddUrl = $opponentAjaxAddUrl ?? $this->Url->build(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'ajaxAdd']);
$placeAjaxAddUrl = $placeAjaxAddUrl ?? $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd']);
$siteAjaxAddUrl = $siteAjaxAddUrl ?? $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'ajaxAdd']);
$gameTypeAjaxAddUrl = $gameTypeAjaxAddUrl ?? $this->Url->build(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'ajaxAdd']);
?>

<!-- Hidden forms for FormProtection tokens -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'ajaxAdd'],
        'id' => 'hidden-opponent-form',
    ]) ?>
    <?= $this->Form->control('opponent_name', ['type' => 'text']) ?>
    <?= $this->Form->control('opponent_mascot', ['type' => 'text']) ?>
    <?= $this->Form->control('opponent_short', ['type' => 'text']) ?>
    <?= $this->Form->control('opponent_abbr', ['type' => 'text']) ?>
    <?= $this->Form->control('opponent_current', ['type' => 'text']) ?>
    <?= $this->Form->control('place_id', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd'],
        'id' => 'hidden-place-form',
    ]) ?>
    <?= $this->Form->control('place_country', ['type' => 'text']) ?>
    <?= $this->Form->control('place_city', ['type' => 'text']) ?>
    <?= $this->Form->control('place_state', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'ajaxAdd'],
        'id' => 'hidden-site-form',
    ]) ?>
    <?= $this->Form->control('site_name', ['type' => 'text']) ?>
    <?= $this->Form->control('place_id', ['type' => 'text']) ?>
    <?= $this->Form->control('capacity', ['type' => 'text']) ?>
    <?= $this->Form->control('site_image', ['type' => 'text']) ?>
    <?= $this->Form->control('site_info', ['type' => 'textarea']) ?>
    <?= $this->Form->end() ?>
</div>
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'ajaxAdd'],
        'id' => 'hidden-game-type-form',
    ]) ?>
    <?= $this->Form->control('game_type_name', ['type' => 'text']) ?>
    <?= $this->Form->control('post', ['type' => 'checkbox']) ?>
    <?= $this->Form->control('conf', ['type' => 'checkbox']) ?>
    <?= $this->Form->control('abr', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<!-- Popup Forms -->
<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-game-type-modal',
    'title' => 'Add New Game Type',
    'formUrl' => $gameTypeAjaxAddUrl,
    'hiddenFormId' => 'hidden-game-type-form',
    'successCallback' => 'handleGameTypeAdded',
    'fields' => [
        ['name' => 'game_type_name', 'type' => 'text', 'label' => 'Name', 'required' => true],
        ['name' => 'abr', 'type' => 'text', 'label' => 'Abbreviation (e.g., NCAA)'],
        ['name' => 'post', 'type' => 'select', 'label' => 'Postseason', 'options' => [0 => 'No', 1 => 'Yes']],
        ['name' => 'conf', 'type' => 'select', 'label' => 'Conference', 'options' => [0 => 'No', 1 => 'Yes']],
    ],
]) ?>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-opponent-modal',
    'title' => 'Add New Opponent',
    'formUrl' => $opponentAjaxAddUrl,
    'hiddenFormId' => 'hidden-opponent-form',
    'successCallback' => 'handleOpponentAdded',
    'fields' => [
        ['name' => 'opponent_name', 'type' => 'text', 'label' => 'Name', 'required' => true],
        ['name' => 'opponent_mascot', 'type' => 'text', 'label' => 'Mascot'],
        ['name' => 'opponent_short', 'type' => 'text', 'label' => 'Short Name (max 30)'],
        ['name' => 'opponent_abbr', 'type' => 'text', 'label' => 'Abbreviation (max 6)'],
        ['name' => 'opponent_current', 'type' => 'text', 'label' => 'Current Opponent ID (self-ref)'],
        ['name' => 'place_id', 'type' => 'hidden', 'label' => 'Place'],
    ],
    'extraHtml' => '<div class="mb-3">'
        . '<label class="form-label">Place</label>'
        . '<div class="input-group">'
        . '<input type="text" class="form-control" id="opponent-place-search" placeholder="Search places..." autocomplete="off">'
        . '<button type="button" class="btn btn-outline-secondary" id="opponent-add-place-btn" title="Add New Place"><i class="bi bi-plus-circle"></i> New</button>'
        . '</div>'
        . '<div id="opponent-place-selected" class="mt-1"></div>'
        . '<div id="opponent-place-results" class="position-relative"></div>'
        . '</div>',
]) ?>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-opponent-place-modal',
    'title' => 'Add New Place (for Opponent)',
    'formUrl' => $placeAjaxAddUrl,
    'hiddenFormId' => 'hidden-place-form',
    'successCallback' => 'handleOpponentPlaceAdded',
    'fields' => [
        ['name' => 'place_country', 'type' => 'text', 'label' => 'Country (ISO 3166 alpha-3)', 'required' => true],
        ['name' => 'place_city', 'type' => 'text', 'label' => 'Locality (city, town, or village)', 'required' => true],
        ['name' => 'place_state', 'type' => 'text', 'label' => 'Subdivision (state, province, or region)'],
    ],
]) ?>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-place-modal',
    'title' => 'Add New Place',
    'formUrl' => $placeAjaxAddUrl,
    'hiddenFormId' => 'hidden-place-form',
    'successCallback' => 'handlePlaceAdded',
    'fields' => [
        ['name' => 'place_country', 'type' => 'text', 'label' => 'Country (ISO 3166 alpha-3)', 'required' => true],
        ['name' => 'place_city', 'type' => 'text', 'label' => 'Locality (city, town, or village)', 'required' => true],
        ['name' => 'place_state', 'type' => 'text', 'label' => 'Subdivision (state, province, or region)'],
    ],
]) ?>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-site-modal',
    'title' => 'Add New Site',
    'formUrl' => $siteAjaxAddUrl,
    'hiddenFormId' => 'hidden-site-form',
    'successCallback' => 'handleSiteAdded',
    'fields' => [
        ['name' => 'site_name', 'type' => 'text', 'label' => 'Site Name', 'required' => true],
        ['name' => 'place_id', 'type' => 'hidden', 'label' => 'Place'],
        ['name' => 'capacity', 'type' => 'text', 'label' => 'Capacity'],
        ['name' => 'site_image', 'type' => 'text', 'label' => 'Image URL'],
        ['name' => 'site_info', 'type' => 'textarea', 'label' => 'Site Info / Notes'],
    ],
]) ?>
