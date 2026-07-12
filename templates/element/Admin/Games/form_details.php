<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $teamSeasonList
 * @var \App\Model\Entity\Game $game
 */
// Game details form element – scheduling/logistics only (no scores, officials, or EAV).
// Expects: $game, $teamSeasonList, $gameTypes, $opponents, $places, $sites, optional $lookupDisplays
$lookupDisplays = $lookupDisplays ?? ['opponent' => null, 'place' => null, 'site' => null, 'gameType' => null];

// Unlock lookup fields so FormProtection allows JS to change their values
$this->Form->unlockField('game_type_id');
$this->Form->unlockField('opponent_id');
$this->Form->unlockField('place_id');
$this->Form->unlockField('site_id');

// Build AJAX endpoint URLs
$opponentSearchUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'ajaxSearch']);
$placeSearchUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch']);
$siteSearchUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'ajaxSearch']);
$gameTypeSearchUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'ajaxSearch']);
?>
<div class="card" id="game-form-card" data-controller="admin-game-form"
     data-opponent-search-url="<?= h($opponentSearchUrl) ?>"
     data-place-search-url="<?= h($placeSearchUrl) ?>"
     data-site-search-url="<?= h($siteSearchUrl) ?>"
     data-game-type-search-url="<?= h($gameTypeSearchUrl) ?>"
     data-opponent-display="<?= h($lookupDisplays['opponent'] ?? '') ?>"
     data-place-display="<?= h($lookupDisplays['place'] ?? '') ?>"
     data-site-display="<?= h($lookupDisplays['site'] ?? '') ?>"
     data-game-type-display="<?= h($lookupDisplays['gameType'] ?? '') ?>">
    <div class="card-header"><h3 class="card-title mb-0">Game Details</h3></div>
    <div class="card-body">
        <?= $this->Form->control('team_season_id', [
            'label' => 'Team Season',
            'type' => 'select',
            'options' => $teamSeasonList,
            'class' => 'form-select',
            'required' => true,
            'id' => 'team-season-select',
            'data-sport-url' => $this->Url->build(['action' => 'sportFormData']),
        ]) ?>
        <div id="sport-indicator" class="mt-2" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Sport:</strong> <span id="current-sport">Loading...</span>
                <div class="spinner-border spinner-border-sm ms-2" role="status" id="sport-loading" style="display: none;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><?= $this->Form->control('game_date', ['type' => 'date', 'class' => 'form-control']) ?></div>
            <div class="col-md-6">
                <?= $this->Form->control('game_time', [
                    'label' => 'Time',
                    'type' => 'time',
                    'class' => 'form-control',
                    'id' => 'game-time-input',
                ]) ?>
                <small class="text-muted">Use time picker or type in 12-hour format (e.g., 7:00 PM)</small>
            </div>
        </div>

        <!-- Game Type: AJAX search + popup -->
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label">Game Type</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="game-type-search"
                           placeholder="Search game types..." autocomplete="off">
                    <?= $this->Form->hidden('game_type_id', ['id' => 'game-type-id', 'value' => $game->game_type_id ?? 1]) ?>
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#add-game-type-modal"
                            title="Add New Game Type">
                        <i class="bi bi-plus-circle"></i> New
                    </button>
                </div>
                <div id="game-type-selected" class="mt-1"></div>
                <div id="game-type-results" class="position-relative"></div>
            </div>
        </div>

        <!-- Hidden field for game_id (for JS/AJAX) -->
        <?php if (isset($game) && isset($game->id)) : ?>
            <input type="hidden" id="game-id-hidden" value="<?= h($game->id) ?>">
        <?php endif; ?>

        <!-- Opponent: AJAX search + popup -->
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label">Opponent</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="opponent-search"
                           placeholder="Search opponents..." autocomplete="off">
                    <?= $this->Form->hidden('opponent_id', ['id' => 'opponent-id', 'value' => $game->opponent_id ?? '']) ?>
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#add-opponent-modal"
                            title="Add New Opponent">
                        <i class="bi bi-plus-circle"></i> New
                    </button>
                </div>
                <div id="opponent-selected" class="mt-1"></div>
                <div id="opponent-results" class="position-relative"></div>
            </div>
        </div>

        <!-- Place: AJAX search + popup -->
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label">Place (City, State)</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="place-search"
                           placeholder="Search places..." autocomplete="off">
                    <?= $this->Form->hidden('place_id', ['id' => 'place-id', 'value' => $game->place_id ?? '']) ?>
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#add-place-modal"
                            title="Add New Place">
                        <i class="bi bi-plus-circle"></i> New
                    </button>
                </div>
                <div id="place-selected" class="mt-1"></div>
                <div id="place-results" class="position-relative"></div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <fieldset class="border rounded p-2" data-controller="place-location">
                    <legend class="float-none w-auto fs-6">New Place</legend>
                    <div class="mb-2">
                        <label class="form-label" for="new-place-country-search">Country Search (common name)</label>
                        <input
                            id="new-place-country-search"
                            type="text"
                            class="form-control"
                            placeholder="Type a country name (e.g., United States)"
                            autocomplete="off"
                            data-place-location-target="countrySearch"
                            data-action="input->place-location#onCountryQuery blur->place-location#onCountryBlur">
                        <div class="mt-1 position-relative" data-place-location-target="countryResults"></div>
                        <small class="text-muted d-block mt-1" data-place-location-target="countryMeta">Select a country to store its ISO3 code and load subdivisions/localities.</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <?= $this->Form->control('new_place.place_country', [
                                'id' => 'new-place-country-code',
                                'label' => 'Country (ISO 3166 alpha-3)',
                                'placeholder' => 'USA',
                                'class' => 'form-control',
                                'readonly' => true,
                                'data-place-location-target' => 'countryCode',
                            ]) ?>
                        </div>
                        <div class="col-md-3">
                            <?= $this->Form->control('new_place.place_city', [
                                'id' => 'new-place-city',
                                'label' => 'Locality (city, town, or village)',
                                'class' => 'form-control',
                                'list' => 'new-place-city-options',
                                'data-place-location-target' => 'city',
                                'data-action' => 'input->place-location#onCityInput blur->place-location#onCityBlur',
                            ]) ?>
                            <datalist id="new-place-city-options" data-place-location-target="cityList"></datalist>
                        </div>
                        <div class="col-md-3">
                            <?= $this->Form->control('new_place.place_state', [
                                'id' => 'new-place-state',
                                'label' => 'Subdivision (state, province, or region)',
                                'class' => 'form-control',
                                'list' => 'new-place-state-options',
                                'data-place-location-target' => 'state',
                                'data-action' => 'input->place-location#onStateInput blur->place-location#onStateBlur',
                            ]) ?>
                            <datalist id="new-place-state-options" data-place-location-target="stateList"></datalist>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2" data-place-location-target="locationMeta">Select a country to load subdivisions and localities.</small>
                </fieldset>
            </div>
        </div>

        <!-- Site: AJAX search + popup (requires place) -->
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label">Site (Arena/Stadium)</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="site-search"
                           placeholder="Search sites..." autocomplete="off">
                    <?= $this->Form->hidden('site_id', ['id' => 'site-id', 'value' => $game->site_id ?? '']) ?>
                    <button type="button" class="btn btn-outline-secondary" id="add-site-btn"
                            data-bs-toggle="modal" data-bs-target="#add-site-modal"
                            title="Add New Site">
                        <i class="bi bi-plus-circle"></i> New
                    </button>
                </div>
                <div id="site-selected" class="mt-1"></div>
                <div id="site-results" class="position-relative"></div>
                <small class="text-muted">Site search is filtered by the selected Place</small>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-4">
                <?= $this->Form->control('hrn', [
                    'label' => 'Location',
                    'type' => 'select',
                    'options' => [1 => 'Home', 2 => 'Road', 3 => 'Neutral'],
                    'empty' => 'Choose...',
                    'class' => 'form-select',
                ]) ?>
            </div>
            <div class="col-md-4"><?= $this->Form->control('post', ['label' => 'Postseason', 'type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
            <div class="col-md-4">
                <?= $this->Form->control('periods', [
                    'label' => 'Periods',
                    'type' => 'select',
                    'options' => [2 => '2 (Halves)', 4 => '4 (Quarters)'],
                    'class' => 'form-select',
                    'default' => 2,
                ]) ?>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <?php
        $cancelUrl = $cancelUrl ?? ['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index'];
        ?>
        <a href="<?= $this->Url->build($cancelUrl) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gameTimeInput = document.getElementById('game-time-input');
    if (gameTimeInput) {
        gameTimeInput.addEventListener('blur', function() {
            const value = this.value.trim();
            const twelveHourPattern = /^(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)$/i;
            const match = value.match(twelveHourPattern);
            if (match) {
                let hours = parseInt(match[1]);
                const minutes = match[2];
                const meridiem = match[3].toUpperCase();
                if (meridiem === 'PM' && hours !== 12) { hours += 12; }
                else if (meridiem === 'AM' && hours === 12) { hours = 0; }
                this.value = String(hours).padStart(2, '0') + ':' + minutes;
            }
        });
    }
});
</script>
