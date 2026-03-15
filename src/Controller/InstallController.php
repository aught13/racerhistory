<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\DeployAuditService;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

/**
 * InstallController
 *
 * Browser-accessible deployment audit (read-only).
 * Mirrors bin/deploy.sh checks without making any changes.
 *
 * Access control:
 * - Always accessible when debug mode is ON (development).
 * - In production (debug OFF), requires ?token= matching the INSTALL_TOKEN env var.
 * - If INSTALL_TOKEN is not set in production, the route is disabled entirely.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class InstallController extends AppController
{
    /**
     * Load Authorization and skip it (public page, token-gated).
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
    }

    /**
     * Gate access before any action runs.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
        $this->gateAccess();
    }

    /**
     * Display the deployment audit results.
     *
     * @return void
     */
    public function index(): void
    {
        $service = new DeployAuditService();
        $audit = $service->run();

        $this->set('audit', $audit);
        $this->set('pageTitle', 'Deployment Audit');
        $this->viewBuilder()->setLayout('install');
    }

    /**
     * Enforce access control: debug mode OR valid install token.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException If access denied.
     */
    private function gateAccess(): void
    {
        // Always allow in debug mode (dev environment)
        if (Configure::read('debug')) {
            return;
        }

        // In production, require INSTALL_TOKEN env var + matching ?token= param
        $envToken = env('INSTALL_TOKEN', '');
        if (!is_string($envToken) || $envToken === '') {
            throw new NotFoundException();
        }

        $requestToken = $this->request->getQuery('token');
        if (!is_string($requestToken) || !hash_equals($envToken, $requestToken)) {
            throw new NotFoundException();
        }
    }
}
