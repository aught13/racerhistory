<?php $this->assign('title', 'Add Place'); ?>
<div class="container py-4">
    <h1 class="mb-3">Add Place</h1>
    <?= $this->Form->create($place) ?>
    <?= $this->Form->control('place_name', ['class' => 'form-control']) ?>
    <div class="row g-3">
        <div class="col-md-6"><?= $this->Form->control('place_city', ['class' => 'form-control']) ?></div>
        <div class="col-md-6"><?= $this->Form->control('place_state', ['class' => 'form-control']) ?></div>
    </div>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
