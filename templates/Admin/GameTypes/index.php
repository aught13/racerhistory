<?php $this->assign('title', 'Game Types'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Game Types</h1>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add</a>
    </div>
    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Post</th><th>Conf</th><th>Ind</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($gameTypes as $gt): ?>
            <tr>
                <td><?= h($gt->game_type_name) ?></td>
                <td><?= $gt->post ? 'Yes' : 'No' ?></td>
                <td><?= $gt->conf ? 'Yes' : 'No' ?></td>
                <td><?= h($gt->ind) ?></td>
                <td class="text-end"><a href="<?= $this->Url->build(['action' => 'edit', $gt->id]) ?>" class="btn btn-sm btn-primary">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
