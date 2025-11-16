<?php $this->assign('title', 'Edit Opponent'); ?>
<div class="container py-4">
    <h1 class="mb-3">Edit Opponent</h1>
    <?= $this->Form->create($opponent) ?>
    <?= $this->Form->control('opponent_name', ['label' => 'Opponent Name', 'class' => 'form-control']) ?>
    <?= $this->Form->control('opponent_mascot', ['label' => 'Mascot/Nickname', 'class' => 'form-control']) ?>
    <?= $this->Form->control('opponent_short', [
        'label' => 'Short Name (max 30 characters)',
        'class' => 'form-control',
        'maxlength' => 30,
    ]) ?>
    <?= $this->Form->control('opponent_abbr', [
        'label' => 'Scorebug Abbreviation (max 6 characters)',
        'class' => 'form-control',
        'maxlength' => 6,
    ]) ?>
    <?= $this->Form->control('place_id', [
        'type' => 'select',
        'label' => 'Location',
        'options' => $places,
        'empty' => 'Choose...',
        'class' => 'form-select',
    ]) ?>
    <?= $this->Form->control('opponent_current', [
        'type' => 'select',
        'label' => 'Current Opponent (if renamed)',
        'options' => $opponentsList,
        'empty' => 'None',
        'class' => 'form-select',
    ]) ?>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
