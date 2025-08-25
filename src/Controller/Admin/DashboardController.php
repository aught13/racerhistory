<?php

declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * Admin Dashboard Controller
 *
 * Handles the main admin dashboard interface.
 * Provides overview information and quick access to administrative functions.
 */
class DashboardController extends AppController
{
    /**
     * Admin dashboard index action.
     *
     * Displays the main admin dashboard with system overview and statistics.
     *
     * @return void
     */
    public function index()
    {
        $this->set('title', 'Admin Dashboard');
        // Example: You can fetch stats, recent users, etc. here
        // $recentUsers = $this->fetchTable('Users')->find()->limit(5)->order(['created' => 'DESC'])->all();
        // $this->set(compact('recentUsers'));
    }
}
