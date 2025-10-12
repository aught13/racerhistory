<?php $this->assign('title', 'Edit Place'); ?>
<div class="container py-4">
    <h1 class="mb-3">Edit Place</h1>
    <?= $this->Form->create($place) ?>
    <?= $this->Form->control('place_name', ['class' => 'form-control']) ?>
    <div class="row g-3">
        <div class="col-md-6"><?= $this->Form->control('place_city', ['class' => 'form-control']) ?></div>
        <div class="col-md-6"><?= $this->Form->control('place_state', ['class' => 'form-control']) ?></div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Sites in this Place</h3>
            <a class="btn btn-sm btn-success" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'add', '?' => ['place_id' => $place->id]]) ?>">Add Site</a>
        </div>
        <div class="card-body">
            <?php if (!empty($sites)) : ?>
                <ul class="mb-0">
                    <?php foreach ($sites as $s) : ?>
                        <li><a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'edit', $s->id]) ?>"><?= h($s->site_name) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <em>No sites added for this place.</em>
            <?php endif; ?>
        </div>
    </div>
</div>
