<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Legacy Admin Sports Controller
 *
 * Compatibility shim for historical `/admin/sports/*` URLs.
 *
 * All canonical sport configuration behavior now lives under
 * SiteOptionsController + SiteOptionsService.
 *
 * Deprecated Sports CRUD actions now redirect with user guidance.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */

class SportsController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * Legacy index now points to SiteOptions sport configs.
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'sportsConfigs',
        ]);
    }

    /**
     * Legacy view route now points to SiteOptions sport configs.
     *
     * @param string|null $sportRef Legacy sport ref (id/key)
     * @return \Cake\Http\Response
     */
    public function view(?string $sportRef = null): Response
    {
        return $this->redirectToSiteOptions('sportsConfigs', $sportRef);
    }

    /**
     * Deprecated sport creation action.
     *
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        $this->Flash->warning(__('Sport CRUD has been retired. Manage sport behavior via Sport Configs.'));

        return $this->redirectToSiteOptions('sportsConfigs');
    }

    /**
     * Deprecated sport edit action.
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response
     */
    public function edit(string $id): Response
    {
        $this->Flash->warning(__('Sport CRUD has been retired. Manage sport behavior via Sport Configs.'));

        return $this->redirectToSiteOptions('sportsConfigs', $id);
    }

    /**
     * Deprecated sport delete action.
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->Flash->warning(__('Sport records are retired and can no longer be deleted.'));

        return $this->redirectToSiteOptions('sportsConfigs');
    }

    /**
     * Deprecated sport bulk delete action.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $this->Flash->warning(__('Sport records are retired and bulk delete is no longer available.'));

        return $this->redirectToSiteOptions('sportsConfigs');
    }

    /**
     * Bulk action dispatcher for sports.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }

        $this->Flash->warning(__('Sport records are retired and bulk actions are no longer available.'));

        return $this->redirectToSiteOptions('sportsConfigs');
    }

    /**
     * Deprecated popup sport creation endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if (!$this->request->is('post')) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => ['Invalid request method.'],
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => [
                    'Sport creation has been retired. Use Site Options > Sport Configs and SportsDefaults.',
                ],
            ]));
    }

    /**
     * Redirect legacy config view URL to SiteOptions config view.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response
     */
    public function configs(?string $sportRef = null): Response
    {
        return $this->redirectToSiteOptions('sportsConfigs', $sportRef);
    }

    /**
     * Redirect legacy config editor URL to SiteOptions config editor.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response
     */
    public function editConfigs(?string $sportRef = null): Response
    {
        return $this->redirectToSiteOptions('editSportConfigs', $sportRef);
    }

    /**
     * Redirect legacy add-config URL to SiteOptions add-config action.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response
     */
    public function addConfig(?string $sportRef = null): Response
    {
        return $this->redirectToSiteOptions('addSportConfig', $sportRef);
    }

    /**
     * Redirect legacy delete-config URL to SiteOptions delete-config action.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @param string $configKey Configuration key
     * @return \Cake\Http\Response Redirects
     */
    public function deleteConfig(?string $sportRef = null, string $configKey = ''): Response
    {
        return $this->redirectToSiteOptions('deleteSportConfig', $sportRef, $configKey);
    }

    /**
     * Redirect legacy reset-config URL to SiteOptions reset action.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response Redirects
     */
    public function resetConfigs(?string $sportRef = null): Response
    {
        return $this->redirectToSiteOptions('resetSportConfigs', $sportRef);
    }

    /**
     * Build a redirect to canonical SiteOptions actions for sport configuration.
     *
     * @param string $action SiteOptions action name
     * @param string|null $sportRef Legacy route ref
     * @param string|null $configKey Optional config key for delete action
     * @return \Cake\Http\Response
     */
    private function redirectToSiteOptions(
        string $action,
        ?string $sportRef = null,
        ?string $configKey = null,
    ): Response {
        $target = [
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => $action,
        ];

        if ($sportRef !== null && trim($sportRef) !== '') {
            $target[] = trim($sportRef);
        }

        if ($configKey !== null && trim($configKey) !== '') {
            $target[] = trim($configKey);
        }

        return $this->redirect($target);
    }
}
