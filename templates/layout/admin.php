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
    <meta name="csrfToken" content="<?= $this->request->getAttribute('csrfToken') ?>">
    <title><?= $this->fetch('title') ?></title>
    <?= $this->Html->meta('icon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <?= $this->Html->css(['cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
    <!-- jQuery for admin pages (used by DataTables and other plugins) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <?= $this->Html->script('admin.js') ?>
</head>

<body>
    <?= $this->element('Admin/nav') ?>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer class="footer bg-light py-3 mt-4">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> RacerHistory Admin</span>
        </div>
    </footer>
    <script>
        // Small runtime check to help debug whether the admin layout and admin.js are executing.
        try {
            console.log('admin layout loaded — URL:', window.location.href);
            console.log('showConfirmDelete present:', typeof window.showConfirmDelete);
        } catch (e) { /* ignore */ }
    </script>
</body>

</html>
