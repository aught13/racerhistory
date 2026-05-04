<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $sites
 */
?>
<?php $this->assign('title', 'Sites'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Sites</h1>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add</a>
    </div>
    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Place</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sites as $s) : ?>
            <tr>
                <td><?= h($s->site_name) ?></td>
                <td><?= h($s->place->place_city ?? '-') ?></td>
                <td class="text-end"><a href="<?= $this->Url->build(['action' => 'edit', $s->id]) ?>" class="btn btn-sm btn-primary">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
