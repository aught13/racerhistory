<?php $this->assign('title', 'Opponents'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Opponents</h1>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add</a>
    </div>
    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Place</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($opponents as $o): ?>
            <tr>
                <td><?= h($o->opponent_name) ?></td>
                <td><?= h($o->place->place_name ?? '-') ?></td>
                <td class="text-end"><a href="<?= $this->Url->build(['action' => 'edit', $o->id]) ?>" class="btn btn-sm btn-primary">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
