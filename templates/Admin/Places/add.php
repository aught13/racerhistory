<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Place $place
 */
?>
<?php $this->assign('title', 'Add Place'); ?>
<div class="container py-4">
    <h1 class="mb-3">Add Place</h1>
    <?= $this->Form->create($place, ['data-controller' => 'place-location']) ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="place-country-search" class="form-label">Country Search (common name)</label>
            <input
                id="place-country-search"
                type="text"
                class="form-control"
                placeholder="Type a country name (e.g., United States)"
                autocomplete="off"
                data-place-location-target="countrySearch"
                data-action="input->place-location#onCountryQuery blur->place-location#onCountryBlur"
            >
            <div class="mt-1 position-relative" data-place-location-target="countryResults"></div>
            <small class="text-muted d-block mt-1" data-place-location-target="countryMeta">Select a country to store its ISO3 code and load subdivisions/localities.</small>
            <?= $this->Form->control('place_country', [
                'class' => 'form-control mt-2',
                'label' => 'Country (ISO 3166 alpha-3)',
                'maxlength' => 3,
                'required' => true,
                'readonly' => true,
                'data-place-location-target' => 'countryCode',
            ]) ?>
        </div>
        <div class="col-md-4">
            <?= $this->Form->control('place_city', [
                'class' => 'form-control',
                'label' => 'Locality (city, town, or village)',
                'required' => true,
                'list' => 'place-city-options',
                'data-place-location-target' => 'city',
                'data-action' => 'input->place-location#onCityInput blur->place-location#onCityBlur',
            ]) ?>
            <datalist id="place-city-options" data-place-location-target="cityList"></datalist>
        </div>
        <div class="col-md-4">
            <?= $this->Form->control('place_state', [
                'class' => 'form-control',
                'label' => 'Subdivision (state, province, or region)',
                'list' => 'place-state-options',
                'data-place-location-target' => 'state',
                'data-action' => 'input->place-location#onStateInput blur->place-location#onStateBlur',
            ]) ?>
            <datalist id="place-state-options" data-place-location-target="stateList"></datalist>
        </div>
    </div>
    <small class="text-muted d-block mt-2" data-place-location-target="locationMeta">Select a country to load subdivisions and localities.</small>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
