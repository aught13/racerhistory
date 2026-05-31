<?php
/**
 * @var \App\View\AppView $this
 * @var array $eav
 * @var mixed $eavTemplate
 * @var mixed $gameTypes
 * @var mixed $legacyMappedEav
 * @var mixed $opponents
 * @var mixed $places
 * @var mixed $sites
 * @var mixed $sportName
 * @var mixed $teamSeasonList
 * @var \App\Model\Entity\Game $game
 */
?>
<?php // Clean production form (debug instrumentation removed) ?>
<input type="hidden" id="game-id-hidden" value="<?= isset($game) && isset($game->id) ? h($game->id) : 'NO_GAME_ID' ?>" />
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
            // Unified AJAX endpoint for sport meta + EAV template
            'data-sport-url' => $this->Url->build(['action' => 'ajaxGameEavMeta']),
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
            <div class="col-md-4"><?= $this->Form->control('game_time', ['label' => 'Time', 'type' => 'text', 'class' => 'form-control']) ?></div>
            <div class="col-md-4"><?= $this->Form->control('game_duration', ['label' => 'Duration', 'type' => 'text', 'class' => 'form-control']) ?></div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6"><?= $this->Form->control('game_type_id', ['label' => 'Game Type', 'type' => 'select', 'options' => $gameTypes, 'empty' => 'Choose...', 'class' => 'form-select']) ?></div>
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
            <div class="col-md-6"><?= $this->Form->control('place_id', ['label' => 'Place (City, State)', 'type' => 'select', 'options' => $places, 'empty' => 'Choose...', 'class' => 'form-select']) ?></div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Place</legend>
                    <div class="row g-2">
                        <div class="col-md-6"><?= $this->Form->control('new_place.place_country', ['label' => 'Country (ISO 3166 alpha-3)', 'placeholder' => 'USA', 'class' => 'form-control']) ?></div>
                        <div class="col-md-3"><?= $this->Form->control('new_place.place_city', ['label' => 'City', 'class' => 'form-control']) ?></div>
                        <div class="col-md-3"><?= $this->Form->control('new_place.place_state', ['label' => 'State', 'class' => 'form-control']) ?></div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6"><?= $this->Form->control('site_id', ['label' => 'Site (Arena/Stadium)', 'type' => 'select', 'options' => $sites, 'empty' => 'Choose...', 'class' => 'form-select']) ?></div>
            <div class="col-md-6">
                <fieldset class="border rounded p-2">
                    <legend class="float-none w-auto fs-6">New Site</legend>
                    <?= $this->Form->control('new_site.site_name', ['label' => 'Name', 'class' => 'form-control']) ?>
                    <small class="text-muted">Uses selected Place</small>
                </fieldset>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-3"><?= $this->Form->control('hrn', ['label' => 'Home (1) / Road (-1) / Neutral (0)', 'type' => 'number', 'class' => 'form-control']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('post', ['label' => 'Postseason', 'type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('w', ['label' => 'W', 'class' => 'form-control']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('l', ['label' => 'L', 'class' => 'form-control']) ?></div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-3"><?= $this->Form->control('pts_mur', ['label' => 'Team Points', 'class' => 'form-control']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('pts_opp', ['label' => 'Opponent Points', 'class' => 'form-control']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('mur_rk', ['label' => 'Team Rank', 'class' => 'form-control']) ?></div>
            <div class="col-md-3"><?= $this->Form->control('opp_rk', ['label' => 'Opponent Rank', 'class' => 'form-control']) ?></div>
        </div>

    <div id="sport-specific-section" class="mt-3" data-initial-loaded="1">
        <?php if (!empty($eavTemplate)) : ?>
            <?= $this->element('Admin/Games/sport_specific_fields', compact('eavTemplate', 'eav', 'legacyMappedEav', 'sportName')) ?>
        <?php else : ?>
            <!-- Fallback Traditional EAV Fields -->
            <div class="row g-3 mt-1">
                <div class="col-md-3"><?= $this->Form->control('periods', ['label' => 'Periods', 'type' => 'number', 'min' => 0, 'max' => 10, 'class' => 'form-control', 'value' => $game->periods ?? ($eav['periods'] ?? '')]) ?></div>
                <div class="col-md-3"><?= $this->Form->control('official_1', ['label' => 'Official 1', 'class' => 'form-control', 'value' => $eav['official_1'] ?? '']) ?></div>
                <div class="col-md-3"><?= $this->Form->control('official_2', ['label' => 'Official 2', 'class' => 'form-control', 'value' => $eav['official_2'] ?? '']) ?></div>
                <div class="col-md-3"><?= $this->Form->control('official_3', ['label' => 'Official 3', 'class' => 'form-control', 'value' => $eav['official_3'] ?? '']) ?></div>
            </div>

            <?php
            $pCount = (int)($game->periods ?? 2);
            $periodCount = max(1, $pCount);
            for ($i = 1; $i <= $periodCount; $i++) : ?>
                <div class="row g-3 mt-1">
                    <div class="col-md-6"><?= $this->Form->control('period_' . $i . '_mur', ['label' => 'Period ' . $i . ' - Team', 'class' => 'form-control', 'value' => $eav['period_' . $i . '_mur'] ?? '']) ?></div>
                    <div class="col-md-6"><?= $this->Form->control('period_' . $i . '_opp', ['label' => 'Period ' . $i . ' - Opponent', 'class' => 'form-control', 'value' => $eav['period_' . $i . '_opp'] ?? '']) ?></div>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
        </div><!-- /sport-specific-section -->
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</div>
