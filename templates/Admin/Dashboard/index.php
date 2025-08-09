<?php
/**
 * Admin Dashboard Index Template
 *
 * Main landing page for the administrative interface. Provides welcome message
 * and entry point for administrative functions.
 *
 * Features:
 * - Authentication status confirmation
 * - Basic dashboard layout structure
 * - Admin navigation integration via layout
 * - Bootstrap styling via admin layout
 *
 * Security:
 * - Admin authentication required
 * - Access controlled by AdminController authorization
 *
 * Future Enhancements:
 * - Dashboard statistics widgets
 * - Quick action buttons
 * - Recent activity feed
 * - System status indicators
 *
 * Variables:
 * @var \App\View\AppView $this
 */

/**
 * Note: Remove the namespace declaration below - it's incorrectly placed in a template file
 * Templates should not contain namespace declarations
 */
// TODO: Remove the following lines - they belong in a PHP class file, not a template
// namespace App\Controller;

$this->assign('title', 'Admin Dashboard');
?>
<div class="admin dashboard">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the administration area. You are authenticated.</p>
</div>
