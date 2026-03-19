<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\DeployAuditService;
use Cake\Cache\Cache;
use Cake\Http\Response;

/**
 * Admin Dashboard Controller
 *
 * Handles the main admin dashboard interface.
 * Provides system health overview and administrative quick actions.
 */
class DashboardController extends AppController
{
    /**
     * Admin dashboard index action.
     *
     * Displays the admin dashboard with deployment audit health checks
     * and quick action buttons.
     *
     * @return void
     */
    public function index(): void
    {
        $service = new DeployAuditService();
        $audit = $service->run();

        $this->set('title', 'Admin Dashboard');
        $this->set('audit', $audit);
    }

    /**
     * Clear all CakePHP cache engines.
     *
     * POST-only action that clears every configured cache engine
     * and redirects back to the dashboard with a flash message.
     *
     * @return \Cake\Http\Response
     */
    public function clearCache(): Response
    {
        $this->request->allowMethod(['post']);

        $cleared = [];
        $failed = [];
        foreach (Cache::configured() as $name) {
            if (Cache::clear($name)) {
                $cleared[] = $name;
            } else {
                $failed[] = $name;
            }
        }

        if (empty($failed)) {
            $this->Flash->success(
                'Cache cleared successfully (' . implode(', ', $cleared) . ').',
            );
        } else {
            $this->Flash->warning(
                'Some caches failed to clear: ' . implode(', ', $failed) . '.',
            );
        }

        return $this->redirect(['action' => 'index']);
    }
}
