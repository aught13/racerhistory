<?php
// Game results form element – scores, rankings, attendance, and EAV (period scores, officials).
// Expects: $game, $eav (array), $eavTemplate (array), $teamSeasonList (for hidden field),
//          optional $sportId, $sportName, $sportHasStats
$eav = $eav ?? [];
$eavTemplate = $eavTemplate ?? [];
$sportId = $sportId ?? 0;
$sportName = $sportName ?? '';
$sportHasStats = $sportHasStats ?? false;
?>
<div class="card" id="game-results-card">
    <div class="card-header"><h3 class="card-title mb-0">Game Results</h3></div>
    <div class="card-body">
        <?php if (!empty($game->opponent) || !empty($game->game_date)) : ?>
            <div class="alert alert-secondary mb-3">
                <strong>Game:</strong>
                <?= h($game->game_date ? $game->game_date->format('M j, Y') : 'Unknown Date') ?>
                <?php if (!empty($game->opponent)) : ?>
                    vs <?= h($game->opponent->opponent_name ?? '') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <?= $this->Form->control('game_duration', [
                    'label' => 'Duration',
                    'type' => 'text',
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 2:30',
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

        <?php if (is_array($eavTemplate) && count($eavTemplate)) : ?>
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
        $cancelUrl = $cancelUrl ?? ['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'view', $game->id ?? null];
        $this->Form->unlockField('save_action');
        ?>
        <a href="<?= $this->Url->build($cancelUrl) ?>" class="btn btn-secondary">Cancel</a>
        <?php if (!empty($sportHasStats) && !empty($game->id)) : ?>
            <button type="submit" name="save_action" value="box_score" class="btn btn-success">
                <i class="bi bi-clipboard-data"></i> Save and Add/Edit Box Score
            </button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Save Results</button>
    </div>
</div>
