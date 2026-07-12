<?php
/**
 * Public layout shell.
 *
 * @var \App\View\AppView $this
 * @var mixed $identity
 * @var bool $isAdmin
 * @var bool $isMainPage
 * @var string $bodyClass
 * @var string $content
 * @var string $flash
 */
?>
<a class="rh-skip-link" href="#main-content">Skip to main content</a>
<div class="rh-page" data-controller="public-shell">
    <header class="rh-header">
        <?php if ($identity) : ?>
        <div class="rh-user-bar" role="status">
            <div class="rh-header-inner rh-header-row">
                <div class="rh-user-info">
                    <span class="rh-user-label">Logged in as</span>
                    <strong><?= h($identity->get('username')) ?></strong>
                    <a class="rh-user-link" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'changePassword', 'plugin' => false]) ?>">Change Password</a>
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

    <div class="rh-nav-wrap rh-unified-nav-wrap" data-nav>
        <?= $this->element('Navigation/sidebar_clean') ?>
    </div>

    <main id="main-content" class="rh-main">
        <div class="rh-main-bg">
            <div class="rh-main-inner">
                <?= $flash ?>
                <?= $content ?>
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
    <button class="rh-scroll-top" type="button" aria-label="Scroll to top" data-action="click->public-shell#scrollToTop">
        <i class="bi bi-arrow-up"></i>
    </button>
</div>
