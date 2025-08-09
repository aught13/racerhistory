<?php
/**
 * Admin Navigation Element
 *
 * Bootstrap navbar component for administrative interface navigation.
 * Provides role-based menu items and authentication status display.
 *
 * Features:
 * - Bootstrap 5 navbar with responsive design
 * - Primary blue background for admin distinction
 * - Collapsible navigation for mobile devices
 * - Authentication status display with username
 * - Admin-specific navigation links
 * - Logout functionality with prefix handling
 *
 * Navigation Structure:
 * - Admin Dashboard: Main admin landing page
 * - Manage Users: User administration interface
 * - Authentication Status: Username display with logout
 *
 * Security:
 * - Authentication status checking
 * - Admin prefix URL generation
 * - Proper logout routing to non-admin controller
 *
 * Responsive Design:
 * - Navbar toggler for mobile menu
 * - Bootstrap grid classes for alignment
 * - Collapse behavior for small screens
 *
 * @var \App\View\AppView $this
 */
// templates/element/Admin/nav.php
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand"
            href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']) ?>">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
            aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link"
                        href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index']) ?>">Manage
                        Users</a>
                </li>
                <!-- Add more admin links here -->
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($this->getRequest()->getAttribute('identity')): ?>
                <li class="nav-item d-flex align-items-center">
                    <span class="navbar-text me-2">Logged in as:
                        <?= h($this->getRequest()->getAttribute('identity')->get('username')) ?></span>
                    <a class="nav-link"
                        href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'logout']) ?>">Logout</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link"
                        href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'login']) ?>">Login</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
