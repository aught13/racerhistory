<?php
/**
 * Install/index template — Deployment Audit results.
 *
 * @var \App\View\AppView $this
 * @var array{results: array<array{category: string, label: string, status: string, detail: string}>, errors: int, warnings: int, overall: string} $audit
 */

$this->assign('title', 'Deployment Audit');

$statusIcon = [
    'ok' => '<i class="bi bi-check-circle-fill status-ok"></i>',
    'warn' => '<i class="bi bi-exclamation-triangle-fill status-warn"></i>',
    'fail' => '<i class="bi bi-x-circle-fill status-fail"></i>',
];

$overallClass = match ($audit['overall']) {
    'pass' => 'bg-success',
    'warn' => 'bg-warning text-dark',
    default => 'bg-danger',
};

$overallLabel = match ($audit['overall']) {
    'pass' => 'All checks passed — production ready!',
    'warn' => 'Warnings found — review before going live.',
    default => 'Errors found — fix before deploying.',
};

// Group results by category
$grouped = [];
foreach ($audit['results'] as $r) {
    $grouped[$r['category']][] = $r;
}
?>

<div class="audit-header py-4 mb-4">
    <div class="container">
        <h1 class="mb-1"><i class="bi bi-gear-wide-connected me-2"></i>RacerHistory — Deployment Audit</h1>
        <p class="mb-0 opacity-75">Read-only environment check &middot; No changes are made</p>
    </div>
</div>

<div class="container pb-5">

    <!-- Overall banner -->
    <div class="alert <?= $overallClass ?> fs-5 d-flex align-items-center mb-4" role="alert">
        <?php if ($audit['overall'] === 'pass') : ?>
            <i class="bi bi-check-circle-fill me-2 fs-4"></i>
        <?php elseif ($audit['overall'] === 'warn') : ?>
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
        <?php else : ?>
            <i class="bi bi-x-circle-fill me-2 fs-4"></i>
        <?php endif; ?>
        <div>
            <strong><?= h($overallLabel) ?></strong>
            <span class="ms-3">
                <?php if ($audit['errors']) : ?>
                    <span class="badge bg-danger"><?= $audit['errors'] ?> error<?= $audit['errors'] !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
                <?php if ($audit['warnings']) : ?>
                    <span class="badge bg-warning text-dark"><?= $audit['warnings'] ?> warning<?= $audit['warnings'] !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- Check categories as accordion -->
    <div class="accordion" id="auditAccordion">
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
            // Expand sections with issues by default
            $expanded = $catHasError || $catHasWarn ? 'true' : 'false';
            $showClass = $catHasError || $catHasWarn ? 'show' : '';
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $i ?>">
                    <button class="accordion-button <?= !$catHasError && !$catHasWarn ? 'collapsed' : '' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>"
                            aria-expanded="<?= $expanded ?>" aria-controls="collapse<?= $i ?>">
                        <i class="bi <?= $catIcon ?> me-2"></i>
                        <?= h($category) ?>
                        <span class="badge <?= $catBadge ?> ms-2"><?= count($items) ?></span>
                    </button>
                </h2>
                <div id="collapse<?= $i ?>" class="accordion-collapse collapse <?= $showClass ?>"
                     aria-labelledby="heading<?= $i ?>" data-bs-parent="#auditAccordion">
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

    <!-- CLI hint -->
    <div class="card mt-4">
        <div class="card-body text-body-secondary">
            <i class="bi bi-terminal me-1"></i>
            For a full deployment (with migrations, cache clearing, and dependency install), use the CLI script:
            <code>bin/deploy.sh</code>
        </div>
    </div>

</div>
