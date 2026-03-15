<?php
// Shared form element for Games add/edit
// Expects: $game, $teamSeasonList, $gameTypes, $opponents, $places, $sites, optional $eav
$eav = $eav ?? [];
?>
<div class="card">
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
            <div class="col-md-4"><?= $this->Form->control('game_date', ['type' => 'date', 'class' => 'form-control']) ?></div>
            <div class="col-md-4">
                <?= $this->Form->control('game_time', [
                    'label' => 'Time',
                    'type' => 'time',
                    'class' => 'form-control',
                    'id' => 'game-time-input',
                ]) ?>
                <small class="text-muted">Use time picker or type in 12-hour format (e.g., 7:00 PM)</small>
            </div>
            <div class="col-md-4">
                <?= $this->Form->control('game_duration', [
                    'label' => 'Duration',
                    'type' => 'text',
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 2:30',
                ]) ?>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <?= $this->Form->control('game_type_id', [
                    'label' => 'Game Type',
                    'type' => 'select',
                    'options' => $gameTypes,
                    'empty' => 'Choose...',
                    'class' => 'form-select',
                    'default' => 1, // Default to ID 1 (Regular Season Game)
                ]) ?>
            </div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Game Type</legend>
                    <?= $this->Form->control('new_game_type.game_type_name', ['label' => 'Name', 'class' => 'form-control']) ?>
                    <div class="row g-2 mt-1">
                        <div class="col"><?= $this->Form->control('new_game_type.post', ['label' => 'Postseason', 'type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
                        <div class="col"><?= $this->Form->control('new_game_type.conf', ['label' => 'Conference', 'type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
                        <div class="col"><?= $this->Form->control('new_game_type.abr', ['label' => 'Abbr (e.g., NCAA)', 'class' => 'form-control', 'maxlength' => 6]) ?></div>
                    </div>
                </fieldset>
                <!-- Hidden field for game_id (for JS/AJAX) -->
                <?php if (isset($game) && isset($game->id)) : ?>
                    <input type="hidden" id="game-id-hidden" value="<?= h($game->id) ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6"><?= $this->Form->control('opponent_id', ['label' => 'Opponent', 'type' => 'select', 'options' => $opponents, 'empty' => 'Choose...', 'class' => 'form-select']) ?></div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Opponent</legend>
                    <?= $this->Form->control('new_opponent.opponent_name', ['label' => 'Name', 'class' => 'form-control']) ?>
                </fieldset>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <?= $this->Form->control('place_id', [
                    'label' => 'Place (City, State)',
                    'type' => 'select',
                    'options' => $places,
                    'empty' => 'Choose...',
                    'class' => 'form-select',
                    'id' => 'place-select',
                ]) ?>
            </div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Place</legend>
                    <div class="row g-2">
                        <div class="col-md-6"><?= $this->Form->control('new_place.place_name', ['label' => 'Name', 'placeholder' => 'City, ST', 'class' => 'form-control']) ?></div>
                        <div class="col-md-3"><?= $this->Form->control('new_place.place_city', ['label' => 'City', 'class' => 'form-control']) ?></div>
                        <div class="col-md-3"><?= $this->Form->control('new_place.place_state', ['label' => 'State', 'class' => 'form-control']) ?></div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <?= $this->Form->control('site_id', [
                    'label' => 'Site (Arena/Stadium)',
                    'type' => 'select',
                    'options' => $sites,
                    'empty' => 'Choose a Place first...',
                    'class' => 'form-select',
                    'id' => 'site-select',
                ]) ?>
            </div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Site</legend>
                    <?= $this->Form->control('new_site.site_name', ['label' => 'Name', 'class' => 'form-control']) ?>
                    <small class="text-muted">Uses selected Place</small>
                </fieldset>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-3">
                <?= $this->Form->control('hrn', [
                    'label' => 'Location',
                    'type' => 'select',
                    'options' => [1 => 'Home', 2 => 'Road', 3 => 'Neutral'],
                    'empty' => 'Choose...',
                    'class' => 'form-select',
                ]) ?>
            </div>
            <div class="col-md-3"><?= $this->Form->control('post', ['label' => 'Postseason', 'type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
            <div class="col-md-3">
                <?= $this->Form->control('periods', [
                    'label' => 'Periods',
                    'type' => 'select',
                    'options' => [2 => '2 (Halves)', 4 => '4 (Quarters)'],
                    'class' => 'form-select',
                    'default' => 2,
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $this->Form->control('ot', [
                    'label' => 'Overtime Periods',
                    'type' => 'number',
                    'min' => 0,
                    'max' => 5,
                    'class' => 'form-control',
                    'default' => 0,
                ]) ?>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <?= $this->Form->control('pts_mur', [
                    'label' => 'Team Points', 'class' => 'form-control',
                    'type' => 'number', 'min' => 0,
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $this->Form->control('pts_opp', [
                    'label' => 'Opponent Points', 'class' => 'form-control',
                    'type' => 'number', 'min' => 0,
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $this->Form->control('mur_rk', [
                    'label' => 'Team Rank', 'class' => 'form-control',
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $this->Form->control('opp_rk', [
                    'label' => 'Opponent Rank', 'class' => 'form-control',
                ]) ?>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <?= $this->Form->control('attendance', [
                    'label' => 'Attendance', 'class' => 'form-control',
                    'type' => 'text', 'maxlength' => 7,
                ]) ?>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i>
            <strong>Note:</strong> Period scores must sum to the final score,
            and regular periods must be tied when overtime is present.
        </div>

        <?php if (isset($eavTemplate) && is_array($eavTemplate) && count($eavTemplate)) : ?>
            <div class="row g-3 mt-1">
                <?php $fieldsPerRow = 4;
                $i = 0; foreach ($eavTemplate as $field) : ?>
                                    <?php if ($i > 0 && $i % $fieldsPerRow === 0) :
                                        ?></div><div class="row g-3 mt-1"><?php
                                    endif; ?>
                    <div class="col-md-3">
                        <?php
                        $value = $eav[$field['field_name']] ?? null;
                        $opts = [
                            'label' => $field['display_label'],
                            'class' => $field['class'] ?? 'form-control',
                            'value' => $value,
                            'type' => $field['field_type'] ?? 'text',
                        ];
                        if (isset($field['min'])) {
                            $opts['min'] = $field['min'];
                        }
                        if (isset($field['max'])) {
                            $opts['max'] = $field['max'];
                        }
                        if (isset($field['maxlength'])) {
                            $opts['maxlength'] = $field['maxlength'];
                        }
                        if (isset($field['placeholder'])) {
                            $opts['placeholder'] = $field['placeholder'];
                        }
                        ?>
                        <?= $this->Form->control($field['field_name'], $opts) ?>
                    </div>
                                <?php $i++;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <?php
        $teamSeasonId = $this->getRequest()->getQuery('team_season_id') ?? ($game->team_season_id ?? null);
        $cancelUrl = $teamSeasonId ?
            ['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId] :
            ['action' => 'index'];
        ?>
        <a href="<?= $this->Url->build($cancelUrl) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const placeSelect = document.getElementById('place-select');
    const siteSelect = document.getElementById('site-select');
    const gameTimeInput = document.getElementById('game-time-input');
    const ajaxUrl = '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'ajaxSitesByPlace']) ?>';

    // Store the initial site_id if editing
    const initialSiteId = <?= json_encode($game->site_id ?? null) ?>;
    const initialPlaceId = <?= json_encode($game->place_id ?? null) ?>;

    function loadSites(placeId, selectSiteId = null) {
        if (!placeId) {
            siteSelect.innerHTML = '<option value="">Choose a Place first...</option>';
            siteSelect.disabled = true;
            return;
        }

        fetch(ajaxUrl + '?place_id=' + placeId)
            .then(response => response.json())
            .then(data => {
                siteSelect.innerHTML = '<option value="">Choose...</option>';
                data.sites.forEach(site => {
                    const option = document.createElement('option');
                    option.value = site.id;
                    option.textContent = site.name;
                    if (selectSiteId && site.id == selectSiteId) {
                        option.selected = true;
                    }
                    siteSelect.appendChild(option);
                });
                siteSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sites:', error);
                siteSelect.innerHTML = '<option value="">Error loading sites</option>';
            });
    }

    // Handle 12-hour time format input
    if (gameTimeInput) {
        gameTimeInput.addEventListener('blur', function() {
            const value = this.value.trim();

            // Check if input is in 12-hour format (contains AM/PM)
            const twelveHourPattern = /^(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)$/i;
            const match = value.match(twelveHourPattern);

            if (match) {
                let hours = parseInt(match[1]);
                const minutes = match[2];
                const meridiem = match[3].toUpperCase();

                // Convert to 24-hour format
                if (meridiem === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (meridiem === 'AM' && hours === 12) {
                    hours = 0;
                }

                // Format as HH:MM for the time input
                const formattedTime = String(hours).padStart(2, '0') + ':' + minutes;
                this.value = formattedTime;
            }
        });
    }

    // Load sites when place changes
    placeSelect.addEventListener('change', function() {
        loadSites(this.value);
    });

    // Load sites on page load if editing and place is selected
    if (initialPlaceId) {
        loadSites(initialPlaceId, initialSiteId);
    }
});
</script>
