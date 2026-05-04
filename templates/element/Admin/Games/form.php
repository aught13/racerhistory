<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $eavTemplate
 * @var mixed $teamSeasonList
 * @var \App\Model\Entity\Game $game
 */
// Shared form element for Games add/edit
// Expects: $game, $teamSeasonList, $gameTypes, $opponents, $places, $sites, optional $eav, $lookupDisplays
$eav = $eav ?? [];
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
<div class="card" id="game-form-card"
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
    const gameTimeInput = document.getElementById('game-time-input');

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
});
</script>
<script type="module">
import { initGameFormLookups } from '/js/game-form-lookups.js';
// Re-initialize on turbo:load for Hotwire compatibility
initGameFormLookups();
</script>
