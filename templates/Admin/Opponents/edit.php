<?php $this->assign('title', 'Edit Opponent'); ?>
<div class="container py-4">
    <h1 class="mb-3">Edit Opponent</h1>
    <?= $this->Form->create($opponent) ?>
    <?= $this->Form->control('opponent_name', ['class' => 'form-control']) ?>
    <?= $this->Form->control('place_id', ['type' => 'select', 'options' => $places, 'empty' => 'Choose...', 'class' => 'form-select']) ?>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
