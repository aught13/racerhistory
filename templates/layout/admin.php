<?php

/**
 * Admin Layout Template — AdminLTE 4
 *
 * Provides the AdminLTE 4 sidebar layout for all admin views.
 *
 * CSS load order:
 *   1. Bootstrap 5 (CDN) — base component styles
 *   2. Bootstrap Icons (CDN) — icon font
 *   3. DataTables Bootstrap 5 skin (CDN) — table plugins
 *   4. Vite bundle — includes AdminLTE 4 structural CSS + Stimulus controllers
 *   5. cake.css — project-specific overrides
 *
 * AdminLTE JS is intentionally NOT loaded. All layout behaviour (sidebar
 * toggle, active-link highlighting) is managed by Stimulus controllers
 * (`admin-layout`, `nav-accordion`) so Turbo Drive page visits work cleanly.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<?php
$content = $this->fetch('content');
$flash = $this->Flash->render();
?>
<html data-bs-theme="light" data-theme="light">

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="reset">
    <meta name="csrfToken" content="<?= $this->request->getAttribute('csrfToken') ?>">
    <title><?= $this->fetch('title') ?> | RacerHistory Admin</title>
    <?= $this->Html->meta('icon') ?>
    <!-- 1. Bootstrap 5 — required peer for AdminLTE 4 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <!-- 2. Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- 3. DataTables — Bootstrap 5 skin -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css">
    <!-- 4. Vite bundle (AdminLTE 4 CSS + compiled Stimulus controllers) -->
    <?php if (class_exists('CakeVite\\View\\Helper\\ViteHelper') || class_exists('Josbeir\\Vite\\View\\Helper\\ViteHelper')) : ?>
        <?php if (method_exists($this->Vite, 'element')) : ?>
            <?= $this->Vite->element('js/main.js') ?>
        <?php else : ?>
            <?php $this->Vite->script(['files' => ['js/main.js']]); ?>
        <?php endif; ?>
    <?php endif; ?>
    <!-- 5. Project-specific overrides -->
    <?= $this->Html->css(['cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <!-- jQuery + DataTables JS (must load before Bootstrap JS so $.fn.DataTable is available) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js"></script>
    <script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/dataTables.searchBuilder.min.js"></script>
    <script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/searchBuilder.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>
    <!-- Bootstrap 5 JS — required for dropdowns, modals, etc. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <?= $this->fetch('script') ?>
</head>

<!--
    AdminLTE 4 body classes:
        sidebar-mini      - enables mini collapsed sidebar mode on desktop
        layout-fixed      - fixed/sticky admin layout rules
        sidebar-expand-lg - desktop sidebar at >= lg, off-canvas at < lg
-->
<body class="sidebar-mini layout-fixed sidebar-expand-lg">
    <?= $this->element('Layout/admin_shell', [
        'content' => $content,
        'flash' => $flash,
    ]) ?>
</body>

</html>
