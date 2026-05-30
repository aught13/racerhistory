<?php
/**
 * @var \App\View\AppView $this
 * @var int $siteCount
 */

$this->assign('title', 'Sites');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'datatables']);
?>

<div class="container-fluid py-4" data-controller="admin-index-table">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Sites</h1>
            <p class="text-muted mb-3">Manage site records and their places. <?= (int)$siteCount ?> total.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Site
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="sites-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="sites-search" class="form-control form-control-sm" placeholder="Name or place..." autocomplete="off" data-admin-index-table-target="searchInput">
            </div>

            <table
                id="sites-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
                data-admin-index-table-target="table"
            >
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Place</th>
                        <th>Capacity</th>
                        <th class="no-sort" style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
