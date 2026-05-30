<?php
/**
 * @var \App\View\AppView $this
 * @var int $placeCount
 */

$this->assign('title', 'Places');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'datatables']);
?>

<div class="container-fluid py-4" data-controller="admin-index-table">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Places</h1>
            <p class="text-muted mb-3">Manage places and locations. <?= (int)$placeCount ?> total.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Place
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="places-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="places-search" class="form-control form-control-sm" placeholder="Country, city, or state..." autocomplete="off" data-admin-index-table-target="searchInput">
            </div>

            <table
                id="places-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
                data-admin-index-table-target="table"
            >
                <thead class="table-dark">
                    <tr>
                        <th>Country</th>
                        <th>City</th>
                        <th>State</th>
                        <th class="no-sort" style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
