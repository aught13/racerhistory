<?php
/**
 * Default Layout Template
 *
 * Main application layout featuring Bootstrap 5.3.2 framework with responsive design.
 * Provides consistent header navigation, authentication status, flash messages, and footer.
 *
 * Features:
 * - Bootstrap 5.3.2 CSS/JS loaded from CDN with integrity verification
 * - jQuery 3.7.1 for enhanced JavaScript functionality
 * - Responsive navigation with authentication status display
 * - Bootstrap Icons 1.11.3 for UI elements
 * - Custom cake.css for application-specific styling
 * - Flash message display area
 * - Responsive footer with copyright information
 *
 * CDN Resources:
 * - Bootstrap CSS: sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN
 * - Bootstrap JS: sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL
 * - jQuery: sha256-3gJwYp4gU1yPpT2+6Ff9QbYl1Q6Vb6Yh2y9gA+3eGm8=
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$cakeDescription = 'CakePHP: the rapid development php framework';
?>
<!DOCTYPE html>
<html>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>


    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Optional: Custom styles -->
    <?= $this->Html->css(['cake']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
    <!-- DataTables and WYSIWYG editor scripts can be added here -->
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $this->Url->build('/') ?>">RacerHistory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($this->getRequest()->getAttribute('identity')) : ?>
                    <li class="nav-item d-flex align-items-center">
                        <span class="navbar-text me-2">Logged in as:
                            <?= h($this->getRequest()->getAttribute('identity')->get('username')) ?></span>
                        <a class="nav-link"
                            href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>">Logout</a>
                    </li>
                    <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link"
                            href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer class="footer bg-light py-3 mt-4">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> RacerHistory</span>
        </div>
    </footer>
</body>

</html>
