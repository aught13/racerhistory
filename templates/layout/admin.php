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
<html>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="reset">
    <meta name="csrfToken" content="<?= $this->request->getAttribute('csrfToken') ?>">
    <title><?= $this->fetch('title') ?> | RacerHistory Admin</title>
    <?= $this->Html->meta('icon') ?>
    <script>
        // Admin UI is intentionally light-only; set theme before CSS paints.
        (function () {
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', 'light');
            root.setAttribute('data-theme', 'light');
            root.classList.remove('dark-mode', 'theme-dark');
        })();
    </script>
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

    data-controller="admin-layout" wires up the Stimulus controller that:
        - toggles sidebar-collapse on desktop
        - toggles sidebar-open on mobile
        - persists desktop collapsed state in localStorage
-->
<body class="sidebar-mini layout-fixed sidebar-expand-lg">
    <div class="app-wrapper" data-controller="admin-layout">

        <!-- ═══════════════════════════════════════════════════════════
             TOP NAVIGATION BAR (data-turbo-permanent: preserved across visits)
             ═══════════════════════════════════════════════════════════ -->
        <nav class="app-header navbar navbar-expand" id="adminHeader" data-turbo-permanent>
            <div class="container-fluid">
                <!-- Left: sidebar toggle + brand -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#" role="button" aria-label="Toggle sidebar"
                            data-action="click->admin-layout#toggle">
                            <i class="bi bi-list fs-4"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex align-items-center ms-2">
                        <a class="navbar-brand fw-semibold"
                            href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']) ?>">
                            RacerHistory Admin
                        </a>
                    </li>
                </ul>

                <!-- Right: identity + logout -->
                <ul class="navbar-nav ms-auto">
                    <?php if ($this->getRequest()->getAttribute('identity')) : ?>
                        <li class="nav-item d-flex align-items-center me-2">
                            <span class="navbar-text">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= h($this->getRequest()->getAttribute('identity')->get('username')) ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                                href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'logout']) ?>"
                                data-turbo-frame="_top">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link"
                                href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'login']) ?>"
                                data-turbo-frame="_top">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- ═══════════════════════════════════════════════════════════
             SIDEBAR (data-turbo-permanent: preserved across visits)
             ═══════════════════════════════════════════════════════════ -->
        <aside class="app-sidebar bg-body-secondary shadow" id="adminSidebar"
            data-bs-theme="dark" data-turbo-permanent>
            <!-- Brand logo area -->
            <div class="sidebar-brand">
                <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']) ?>"
                    class="brand-link">
                    <i class="bi bi-mortarboard-fill brand-image me-2 text-warning"></i>
                    <span class="brand-text fw-semibold">RacerHistory</span>
                </a>
            </div>

            <!-- Navigation menu -->
            <div class="sidebar-wrapper">
                <?= $this->element('Admin/nav') ?>
            </div>
        </aside>

        <div class="sidebar-overlay" data-action="click->admin-layout#closeMobile"></div>

        <!-- ═══════════════════════════════════════════════════════════
             MAIN CONTENT AREA
             ═══════════════════════════════════════════════════════════ -->
        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid py-3">
                    <turbo-frame id="admin-content" data-turbo-action="advance">
                        <?= $this->Flash->render() ?>
                        <?= $this->fetch('content') ?>
                    </turbo-frame>
                </div>
            </div>
        </main>

        <!-- ═══════════════════════════════════════════════════════════
             FOOTER
             ═══════════════════════════════════════════════════════════ -->
        <footer class="app-footer">
            <strong>&copy; <?= date('Y') ?> RacerHistory Admin</strong>
            <span class="float-end d-none d-sm-inline text-muted">Powered by CakePHP</span>
        </footer>

    </div><!-- /.app-wrapper -->
</body>

</html>
