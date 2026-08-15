<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */

$this->assign('title', 'Site Options');
?>

<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-2">Site Options</h1>
            <p class="text-muted mb-0">Manage global runtime configuration for registration, maintenance mode, and defaults.</p>
        </div>
    </div>

    <?= $this->element('Admin/site_options_form', [
        'siteOptionDefinitions' => $siteOptionDefinitions,
        'siteOptions' => $siteOptions,
    ]) ?>
</div>
