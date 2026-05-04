<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $places
 */
?>
<?php $this->assign('title', 'Places'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Places</h1>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add</a>
    </div>
    <table class="table table-striped">
        <thead><tr><th>Country</th><th>Locality</th><th>Subdivision</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($places as $p) : ?>
            <tr>
                <td><?= h($p->place_country) ?></td>
                <td><?= h($p->place_city) ?></td>
                <td><?= h($p->place_state) ?></td>
                <td class="text-end"><a href="<?= $this->Url->build(['action' => 'edit', $p->id]) ?>" class="btn btn-sm btn-primary">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
