<?php

/**
 * @var \App\View\AppView $this
 * @var int $imageCount
 */
$this->assign('title', 'Images');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'datatables']);
?>

<div class="container-fluid py-4" data-controller="admin-index-table">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Images</h1>
            <p class="text-muted mb-3">
                All Images: <?= (int)$imageCount ?> total.
            </p>
            <a href="<?= $this->Url->build(['action' => 'bulkUploadForm']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-upload"></i> Upload Images
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="images-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="images-search" class="form-control form-control-sm" placeholder="Name, mime, status, id..." autocomplete="off" data-admin-index-table-target="searchInput">
            </div>

            <table
                id="images-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
                data-admin-index-table-target="table"
            >
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th style="width: 5rem;">Preview</th>
                        <th>Original Name</th>
                        <th>Mime</th>
                        <th>Size</th>
                        <th>Dimensions</th>
                        <th>Status</th>
                        <th style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
