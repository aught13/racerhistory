<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\DeployAuditService;
use App\Service\SportConfigService;
use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * Admin Dashboard Controller
 *
 * Handles the main admin dashboard interface.
 * Provides system health overview and administrative quick actions.
 * The index action displays deployment audit results to help administrators monitor the health of the application and identify any issues that may need attention. The clearCache action allows administrators to clear all configured cache engines with a single click, which can be useful for troubleshooting and ensuring that changes are reflected immediately.
 * All actions in this controller should be protected by authentication and authorization checks to ensure that only authorized users can access the dashboard and perform administrative actions. This is typically handled by middleware or components that are not shown in this code snippet.
 * The controller relies on the DeployAuditService to perform health checks and gather relevant information about the application's deployment status, which is then passed to the view for display. The clearCache action uses CakePHP's Cache class to clear all configured cache engines and provides feedback to the administrator through flash messages.
 * The controller is designed to be thin and focused on request handling and response formatting, while delegating business logic and data retrieval to services like DeployAuditService. This separation of concerns helps keep the code organized and maintainable.
 *
 * Actions:
 * - index: Displays the admin dashboard with deployment audit health checks and quick action buttons. The audit results should be carefully reviewed to ensure that sensitive information is not exposed in the dashboard view, and
 * appropriate measures should be taken to secure any sensitive data that may be included in the audit results.
 * - clearCache: POST-only action that clears every configured cache engine and redirects back to the dashboard with a flash message indicating the success or failure of the cache clearing operation. This action should be used with caution, as clearing caches can impact application performance and user experience if done frequently or without proper
 * consideration of the consequences.
 *
 * Security:
 * - The clearCache action is restricted to POST requests to prevent accidental cache clearing via GET requests.
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can access the dashboard and perform administrative actions.
 * - The deployment audit results should be carefully reviewed to ensure that sensitive information is not exposed in the dashboard view, and appropriate measures should be taken to secure any sensitive data that may be included in the audit results.
 *
 * Dependencies:
 * - DeployAuditService: Provides methods for performing deployment audits and gathering health check information about the application. This service abstracts the logic of performing health checks and allows the controller to focus on handling requests and formatting responses.
 * - CakePHP Cache class: Used to clear configured cache engines in the clearCache action. This allows administrators to easily clear caches without needing to access the server or use command-line tools.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after clearing caches, providing feedback to the administrator about the outcome of their actions.
 * - AuthorizationComponent: Used to protect all actions in this controller, ensuring that only authorized users can access the dashboard and perform administrative actions. This is typically
 * configured to require authentication and specific permissions for accessing the dashboard and performing actions like clearing caches.
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types, although in this implementation we manually check for POST requests in the clearCache action to ensure that it is only accessible via POST.
 *
 * Note: The deployment audit results should be carefully reviewed to ensure that sensitive information is not exposed in the dashboard view, and appropriate measures should be taken to secure any sensitive data that may be included in the audit results. Additionally, the clearCache action should be used with caution, as clearing caches can impact application performance and user experience if done frequently or without proper consideration of the consequences.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
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

        $locator = TableRegistry::getTableLocator();
        $sportCount = count((new SportConfigService())->getAvailableSports());
        $counts = [
            'sports' => $sportCount,
            'teams' => $locator->get('Teams')->find()->count(),
            'games' => $locator->get('Games')->find()->count(),
            'images' => $locator->get('Images')->find()->count(),
        ];

        $this->set('title', 'Admin Dashboard');
        $this->set('audit', $audit);
        $this->set('counts', $counts);
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
