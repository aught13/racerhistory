<?php
/**
 * Admin Dashboard Index Template
 *
 * Main landing page for the administrative interface. Provides system health
 * checks (deployment audit) and quick action buttons for common tasks.
 *
 * @var \App\View\AppView $this
 * @var array{results: array<array{category: string, label: string, status: string, detail: string}>, errors: int, warnings: int, overall: string} $audit
 */

$this->assign('title', 'Admin Dashboard');
$this->Html->script('admin-dashboard.js', ['block' => true]);

$statusIcon = [
    'ok' => '<i class="bi bi-check-circle-fill text-success"></i>',
    'warn' => '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
    'fail' => '<i class="bi bi-x-circle-fill text-danger"></i>',
];

$overallClass = match ($audit['overall']) {
    'pass' => 'bg-success',
    'warn' => 'bg-warning text-dark',
    default => 'bg-danger',
};

$overallLabel = match ($audit['overall']) {
    'pass' => 'All checks passed',
    'warn' => 'Warnings found — review recommended',
    default => 'Errors found — action required',
};

// Group results by category
$grouped = [];
foreach ($audit['results'] as $r) {
    $grouped[$r['category']][] = $r;
}
?>
<div class="admin dashboard">
    <h1 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= $this->Form->create(null, [
                            'url' => ['controller' => 'Dashboard', 'action' => 'clearCache', 'prefix' => 'Admin'],
                            'id' => 'clear-cache-form',
                        ]) ?>
                            <button type="submit" class="btn btn-outline-warning" id="btn-clear-cache">
                                <i class="bi bi-trash3 me-2"></i>Clear CakePHP Cache
                            </button>
                        <?= $this->Form->end() ?>

                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'index', 'prefix' => 'Admin']) ?>"
                           class="btn btn-outline-primary">
                            <i class="bi bi-people me-2"></i>Manage Users
                        </a>

                        <a href="<?= $this->Url->build(['controller' => 'Images', 'action' => 'bulkUploadForm', 'prefix' => 'Admin']) ?>"
                           class="btn btn-outline-primary">
                            <i class="bi bi-upload me-2"></i>Upload Images
                        </a>

                        <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'index', 'prefix' => 'Admin']) ?>"
                           class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square me-2"></i>Manage Blog
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>System Health</h5>
                    <span class="badge <?= $overallClass ?>">
                        <?= h($overallLabel) ?>
                        <?php if ($audit['errors']) : ?>
                            &middot; <?= $audit['errors'] ?> error<?= $audit['errors'] !== 1 ? 's' : '' ?>
                        <?php endif; ?>
                        <?php if ($audit['warnings']) : ?>
                            &middot; <?= $audit['warnings'] ?> warning<?= $audit['warnings'] !== 1 ? 's' : '' ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="accordion accordion-flush" id="healthAccordion">
                        <?php $i = 0;
                        foreach ($grouped as $category => $items) :
                            $i++; ?>
                            <?php
                            $catHasError = false;
                            $catHasWarn = false;
                            foreach ($items as $item) {
                                if ($item['status'] === 'fail') {
                                    $catHasError = true;
                                }
                                if ($item['status'] === 'warn') {
                                    $catHasWarn = true;
                                }
                            }
                            $catBadge = $catHasError ? 'bg-danger' : ($catHasWarn ? 'bg-warning text-dark' : 'bg-success');
                            $catIcon = $catHasError ? 'bi-x-circle-fill text-danger' : ($catHasWarn ? 'bi-exclamation-triangle-fill text-warning' : 'bi-check-circle-fill text-success');
                            $expanded = $catHasError || $catHasWarn ? 'true' : 'false';
                            $showClass = $catHasError || $catHasWarn ? 'show' : '';
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="healthHeading<?= $i ?>">
                                    <button class="accordion-button <?= !$catHasError && !$catHasWarn ? 'collapsed' : '' ?>"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#healthCollapse<?= $i ?>"
                                            aria-expanded="<?= $expanded ?>"
                                            aria-controls="healthCollapse<?= $i ?>">
                                        <i class="bi <?= $catIcon ?> me-2"></i>
                                        <?= h($category) ?>
                                        <span class="badge <?= $catBadge ?> ms-2"><?= count($items) ?></span>
                                    </button>
                                </h2>
                                <div id="healthCollapse<?= $i ?>"
                                     class="accordion-collapse collapse <?= $showClass ?>"
                                     aria-labelledby="healthHeading<?= $i ?>"
                                     data-bs-parent="#healthAccordion">
                                    <div class="accordion-body p-0">
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($items as $item) : ?>
                                                <li class="list-group-item d-flex align-items-start">
                                                    <span class="me-2 mt-1"><?= $statusIcon[$item['status']] ?></span>
                                                    <div>
                                                        <span><?= h($item['label']) ?></span>
                                                        <?php if ($item['detail']) : ?>
                                                            <br><small class="text-body-secondary"><?= h($item['detail']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
