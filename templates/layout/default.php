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
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="reset">
    <meta name="theme-color" content="#002144">
    <link rel="manifest" href="<?= $this->Url->build('/manifest.webmanifest') ?>">
    <link rel="apple-touch-icon" href="<?= $this->Url->build('/img/logo.png') ?>">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <script>
        (function () {
            var match = document.cookie.match(/(?:^|; )theme=([^;]*)/);
            var theme = match ? decodeURIComponent(match[1]) : '';
            if (theme === 'light' || theme === 'dark') {
                document.documentElement.dataset.theme = theme;
            } else {
                delete document.documentElement.dataset.theme;
            }
        })();
    </script>


    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Optional: Custom styles -->
    <?= $this->Html->css(['cake']) ?>
    <?= $this->Html->css(['frontend']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

    <!-- Import maps + Hotwire (Turbo/Stimulus) -->
    <?= $this->Html->importmap(require CONFIG . 'importmap.php') ?>
    <?= $this->Html->script('hotwire/application', ['type' => 'module']) ?>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
    <!-- DataTables and WYSIWYG editor scripts can be added here -->
    <script>
        function initNavBehavior() {
            const body = document.body;
            const head = document.querySelector('.rh-head');
            const navLogo = document.querySelector('.rh-nav-logo');
            const navWrap = document.querySelector('.rh-nav-wrap');
            const scrollTopBtn = document.querySelector('.rh-scroll-top');

            function setNavState(isStuck) {
                if (isStuck) {
                    body.classList.add('rh-nav-stuck');
                    body.classList.add('rh-head-collapsed');
                } else {
                    body.classList.remove('rh-nav-stuck');
                    body.classList.remove('rh-head-collapsed');
                }
            }

            function updateNavState() {
                if (!head || !navLogo) {
                    setNavState(true);
                    return;
                }

                const navHeight = navWrap?.offsetHeight ?? 0;
                const headHeight = head.offsetHeight ?? 0;
                const isStuck = window.scrollY >= Math.max(0, headHeight - navHeight);
                setNavState(isStuck);
            }

            function handleScrollTopButton() {
                if (!scrollTopBtn) {
                    return;
                }
                const current = window.scrollY;
                const show = current > (window.innerHeight * 1.25);
                scrollTopBtn.classList.toggle('is-visible', show);
            }

            updateNavState();
            handleScrollTopButton();

            window.addEventListener('scroll', updateNavState, { passive: true });
            window.addEventListener('scroll', handleScrollTopButton, { passive: true });

            scrollTopBtn?.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        document.addEventListener('DOMContentLoaded', initNavBehavior);
        document.addEventListener('turbo:load', initNavBehavior);
    </script>
</head>

<?php
$identity = $this->getRequest()->getAttribute('identity');
$role = $identity && method_exists($identity, 'get') ? (string)$identity->get('role') : '';
$isAdmin = $identity && (
    in_array($role, ['admin', 'superadmin'], true) ||
    (bool)($identity->get('is_superuser') ?? false)
);
$controller = (string)$this->request->getParam('controller');
$action = (string)$this->request->getParam('action');
$isMainPage = $action === 'index' && in_array($controller, ['Blog', 'Seasons', 'People', 'Stats', 'Games'], true);
$bodyClass = trim(($identity ? 'rh-has-user ' : '') . ($isMainPage ? 'rh-has-head' : ''));
?>
<body class="<?= h($bodyClass) ?>" data-is-main="<?= $isMainPage ? 'true' : 'false' ?>">
    <a class="rh-skip-link" href="#main-content">Skip to main content</a>
    <div class="rh-page">
        <header class="rh-header">
            <?php if ($identity) : ?>
            <div class="rh-user-bar" role="status">
                <div class="rh-header-inner rh-header-row">
                    <div class="rh-user-info">
                        <span class="rh-user-label">Logged in as</span>
                        <strong><?= h($identity->get('username')) ?></strong>
                        <a class="rh-user-link" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout', 'plugin' => false]) ?>">Logout</a>
                    </div>
                    <?php if ($isAdmin) : ?>
                        <a class="rh-admin-link" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>">Admin Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isMainPage) : ?>
            <div class="rh-head" data-head>
                <div class="rh-header-inner rh-head-inner">
                    <div class="rh-head-logo">
                        <img src="<?= $this->Url->build('/img/logo.png') ?>" alt="RacerHistory" class="rh-hero-logo-img">
                    </div>
                    <div class="rh-ad-slot rh-ad-slot--header">Ad</div>
                </div>
            </div>
            <?php endif; ?>

        </header>

        <div class="rh-nav-wrap" data-nav>
            <div class="rh-header-inner rh-nav-inner">
                <nav class="navbar navbar-expand-lg rh-navbar" data-bs-theme="dark">
                    <a class="navbar-brand rh-logo-link" href="<?= $this->Url->build('/') ?>" aria-label="RacerHistory Home">
                        <img src="<?= $this->Url->build('/img/logo.png') ?>" alt="" style="max-height: 32px; object-fit: contain;" loading="eager" class="rh-nav-logo">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'index', 'plugin' => false]) ?>">Seasons</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $this->Url->build(['controller' => 'People', 'action' => 'index', 'plugin' => false]) ?>">People</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $this->Url->build(['controller' => 'Stats', 'action' => 'index', 'plugin' => false]) ?>">Stats</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index', 'plugin' => false]) ?>">Games</a>
                            </li>
                        </ul>
                        <div class="rh-theme-toggle"></div>
                    </div>
                </nav>
                <div class="rh-ad-slot rh-ad-slot--nav">Ad</div>
            </div>
        </div>

        <main id="main-content" class="rh-main">
            <div class="rh-main-bg">
                <div class="rh-main-inner">
                    <?= $this->Flash->render() ?>
                    <?= $this->fetch('content') ?>
                </div>
            </div>
        </main>

        <footer class="rh-footer">
            <div class="rh-footer-inner">
                <div class="rh-footer-ad">Ad</div>
                <div class="rh-footer-copy">
                    <span class="text-muted">&copy; <?= date('Y') ?> RacerHistory</span>
                </div>
                <div class="rh-footer-controls">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-controller="theme-toggle"
                        data-action="click->theme-toggle#toggle"
                        aria-pressed="false">
                        <i class="bi bi-circle-half" aria-hidden="true"></i>
                        <span class="ms-1" data-theme-toggle-target="label">System</span>
                    </button>
                </div>
            </div>
        </footer>
    </div>
    <button class="rh-scroll-top" type="button" aria-label="Scroll to top">
        <i class="bi bi-arrow-up"></i>
    </button>
</body>

</html>
