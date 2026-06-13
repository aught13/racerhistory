<?php
/**
 * Admin Sidebar Navigation Element — AdminLTE 4
 *
 * Renders the sidebar menu used by `templates/layout/admin.php`.
 *
 * Active-link highlighting is driven by PHP (no JS required).
 * The `nav-accordion` Stimulus controller auto-expands the section group
 * that matches the current URL so the correct group is always open.
 *
 * Structure:
 *   - Dashboard (top-level direct link)
 *   - SPORTS group: Sports, Teams, Seasons, Team Seasons,
 *       Games, Game Types, Opponents, Persons, Places, Sites
 *   - CONTENT group: Blog Posts, Images
 *   - ADMINISTRATION group: Users
 *
 * @var \App\View\AppView $this
 */

$currentController = $this->request->getParam('controller');

/**
 * Returns the CSS active class string if the current controller is in the provided list.
 *
 * @param string ...$controllers
 * @return string
 */
$isActive = function (string ...$controllers) use ($currentController): string {
    return in_array($currentController, $controllers, true) ? ' active' : '';
};

$u = fn(array $r): string => $this->Url->build($r);
?>
<nav class="mt-2" data-controller="nav-accordion">
    <ul class="nav sidebar-menu flex-column" role="menu">

        <!-- ── Dashboard ───────────────────────────────────────────── -->
        <li class="nav-item">
            <a class="nav-link<?= $isActive('Dashboard') ?>"
               href="<?= $u(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>"
               data-turbo-frame="admin-content">
                <i class="nav-icon bi bi-speedometer2"></i>
                <p>Dashboard</p>
            </a>
        </li>

        <!-- ── Sports section ────────────────────────────────────────── -->
        <li class="nav-header">SPORTS</li>

        <li class="nav-item w-100">
            <button type="button"
                    class="nav-link border-0 bg-transparent w-100 text-start<?= $isActive('Sports', 'Teams', 'Seasons', 'TeamSeasons', 'Games', 'GameTypes', 'Opponents', 'Persons', 'Places', 'Sites') ?>"
                    data-nav-accordion-target="toggle"
                    data-nav-accordion-prefix="/admin/sports|/admin/teams|/admin/seasons|/admin/team-seasons|/admin/games|/admin/game-types|/admin/opponents|/admin/persons|/admin/places|/admin/sites"
                    aria-expanded="false"
                    data-action="click->nav-accordion#toggle">
                <i class="nav-icon bi bi-trophy"></i>
                <p>Sports
                    <i class="nav-arrow bi bi-chevron-down ms-auto"></i>
                </p>
            </button>
            <ul class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Sports') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Sports</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Teams') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Teams</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Seasons') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Seasons</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('TeamSeasons') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Team Seasons</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Games') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Games</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('GameTypes') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Game Types</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Opponents') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Opponents</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Persons') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Persons</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Places') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Places</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Sites') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-dash-circle"></i>
                            <p>Sites</p>
                        </a>
                    </li>
                </ul>
        </li>

        <!-- ── Content section ──────────────────────────────────────── -->
        <li class="nav-header">CONTENT</li>

        <li class="nav-item w-100">
            <button type="button"
                    class="nav-link border-0 bg-transparent w-100 text-start<?= $isActive('BlogPosts', 'Images') ?>"
                    data-nav-accordion-target="toggle"
                    data-nav-accordion-prefix="/admin/blog|/admin/images"
                    aria-expanded="false"
                    data-action="click->nav-accordion#toggle">
                <i class="nav-icon bi bi-files"></i>
                <p>Content
                    <i class="nav-arrow bi bi-chevron-down ms-auto"></i>
                </p>
            </button>
            <ul class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('BlogPosts') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'BlogPosts', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-pencil-square"></i>
                            <p>Blog Posts</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4<?= $isActive('Images') ?>"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'index']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-images"></i>
                            <p>Images</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ps-4"
                           href="<?= $u(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'bulkUploadForm']) ?>"
                           data-turbo-frame="admin-content">
                            <i class="nav-icon bi bi-upload"></i>
                            <p>Upload Images</p>
                        </a>
                    </li>
                </ul>
        </li>

        <!-- ── Administration section ───────────────────────────────── -->
        <li class="nav-header">ADMINISTRATION</li>

        <li class="nav-item">
            <a class="nav-link<?= $isActive('Users') ?>"
               href="<?= $u(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index']) ?>"
               data-turbo-frame="admin-content">
                <i class="nav-icon bi bi-people-fill"></i>
                <p>Users</p>
            </a>
        </li>

    </ul>
</nav>
