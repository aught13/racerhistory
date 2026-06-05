<?php

/**
 * Admin Layout Template
 *
 * Administrative interface layout providing a clean, functional design for admin operations.
 * Features simplified Bootstrap styling optimized for administrative tasks and data management.
 *
 * Features:
 * - Bootstrap 5.3.2 framework for consistent admin UI
 * - Admin navigation element integration
 * - Bootstrap Icons for admin interface elements
 * - Custom cake.css for admin-specific styling
 * - jQuery support for enhanced admin functionality
 * - Container-based layout for admin content
 *
 * Security:
 * - Admin-only access required
 * - Authentication status integration
 * - CSRF protection for admin forms
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
    <title><?= $this->fetch('title') ?></title>
    <?= $this->Html->meta('icon') ?>
    <script>
        // Admin UI is intentionally light-only; set this before CSS paints.
        (function () {
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', 'light');
            root.setAttribute('data-theme', 'light');
            root.classList.remove('dark-mode', 'theme-dark');
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css">
    <?= $this->Html->css(['cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <?php if (class_exists('CakeVite\\View\\Helper\\ViteHelper') || class_exists('Josbeir\\Vite\\View\\Helper\\ViteHelper')) : ?>
        <?php if (method_exists($this->Vite, 'element')) : ?>
            <?= $this->Vite->element('js/main.js') ?>
        <?php else : ?>
            <?php $this->Vite->script(['files' => ['js/main.js']]); ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- jQuery for admin pages (used by DataTables and other plugins) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js"></script>
    <script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/dataTables.searchBuilder.min.js"></script>
    <script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/searchBuilder.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <?= $this->fetch('script') ?>
</head>

<body>
    <?= $this->element('Admin/nav') ?>
    <main class="main">
        <div class="container">
            <turbo-frame id="admin-content" data-turbo-action="advance">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </turbo-frame>
        </div>
    </main>
    <footer class="footer bg-light py-3 mt-4">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> RacerHistory Admin</span>
        </div>
    </footer>
</body>

</html>
