<?php
/**
 * Admin layout shell.
 *
 * @var \App\View\AppView $this
 * @var string $content
 * @var string $flash
 */
?>
<div class="app-wrapper" data-controller="admin-layout">
    <nav class="app-header navbar navbar-expand" id="adminHeader" data-turbo-permanent>
        <div class="container-fluid">
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

    <aside class="app-sidebar bg-body-secondary shadow" id="adminSidebar"
        data-bs-theme="dark" data-turbo-permanent>
        <div class="sidebar-brand">
            <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']) ?>"
                class="brand-link">
                <i class="bi bi-mortarboard-fill brand-image me-2 text-warning"></i>
                <span class="brand-text fw-semibold">RacerHistory</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <?= $this->element('Admin/nav') ?>
        </div>
    </aside>

    <div class="sidebar-overlay" data-action="click->admin-layout#closeMobile"></div>

    <main class="app-main">
        <div class="app-content">
            <div class="container-fluid py-3">
                <turbo-frame id="admin-content" data-turbo-action="advance">
                    <?= $flash ?>
                    <?= $content ?>
                </turbo-frame>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>&copy; <?= date('Y') ?> RacerHistory Admin</strong>
        <span class="float-end d-none d-sm-inline text-muted">Powered by CakePHP</span>
    </footer>
</div>