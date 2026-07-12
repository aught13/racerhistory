<?php
declare(strict_types=1);

/**
 * @var array<int,\App\Model\Entity\Person> $people
 * @var array<int,array{person:\App\Model\Entity\Person,teams:array<int,string>,years:array<int,string>}> $peopleRows
 * @var int $peopleCount
 * @var \App\View\AppView $this
 */
$this->assign('title', 'People');

echo $this->element('People/table_assets');
?>
<div class="container py-4" data-controller="people-index">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">People</h1>
        <p class="text-muted mb-0">Players, coaches, and staff</p>
    </div>

    <?php if (!empty($peopleRows) || ($peopleCount ?? 0) > 0) : ?>
        <div class="people-searchbar input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="people-name-search" class="form-control" placeholder="Search people by name...">
        </div>

        <div class="people-table-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="people-table"
                       data-people-data-url="<?= h($this->Url->build(['controller' => 'People', 'action' => 'index', '?' => ['format' => 'json']])) ?>">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Teams</th>
                            <th>Years Active</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No people records available yet.
        </div>
    <?php endif; ?>
</div>
