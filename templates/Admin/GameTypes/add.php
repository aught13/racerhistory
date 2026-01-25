<?php $this->assign('title', 'Add Game Type'); ?>
<div class="container py-4">
    <h1 class="mb-3">Add Game Type</h1>
    <?= $this->Form->create($gameType) ?>
    <?= $this->Form->control('game_type_name', ['class' => 'form-control']) ?>
    <div class="row g-3">
        <div class="col-md-3"><?= $this->Form->control('post', ['type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
        <div class="col-md-3"><?= $this->Form->control('conf', ['type' => 'checkbox', 'class' => 'form-check-input']) ?></div>
        <div class="col-md-6"><?= $this->Form->control('abr', ['label' => 'Abbr', 'class' => 'form-control', 'maxlength' => 6, 'help' => 'Required when Post or Conf is set.']) ?></div>
    </div>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
