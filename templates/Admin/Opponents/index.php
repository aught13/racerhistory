<?php
/**
 * @var \App\View\AppView $this
 * @var int $opponentCount
 */

$this->assign('title', 'Opponents');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'datatables']);
$canCreateOpponents = $this->Rbac->can('Opponents', 'create');
?>

<div class="container-fluid py-4" data-controller="admin-index-table">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Opponents</h1>
            <p class="text-muted mb-3">Manage opponent records. <?= (int)$opponentCount ?> total.</p>
            <?php if ($canCreateOpponents) : ?>
                <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Opponent
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="opponents-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="opponents-search" class="form-control form-control-sm" placeholder="Name, short, abbr, or place..." autocomplete="off" data-admin-index-table-target="searchInput">
            </div>

            <table
                id="opponents-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
                data-admin-index-table-target="table"
            >
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Short</th>
                        <th>Abbr</th>
                        <th>Place</th>
                        <th class="no-sort" style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
