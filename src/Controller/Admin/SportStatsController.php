<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin Sport Stats Controller
 *
 * Backward-compatible controller for legacy /admin/sport-stats routes.
 *
 * The old SportStatRegistry CRUD surface has been retired. These actions now
 * redirect users to SiteOptions sport configuration pages where stat behavior
 * is managed via SiteOptions-backed sport configs.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class SportStatsController extends AppController
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
     * Redirect legacy stat-registry index to SiteOptions sport configs.
     *
     * @param string|null $sportId Sport ID to filter by
     * @return \Cake\Http\Response
     */
    public function index(?string $sportId = null): Response
    {
        if ($sportId === null || trim($sportId) === '') {
            return $this->redirect([
                'prefix' => 'Admin',
                'controller' => 'SiteOptions',
                'action' => 'sportsConfigs',
            ]);
        }

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'sportsConfigs',
            trim($sportId),
        ]);
    }

    /**
     * Redirect legacy stat-registry view to SiteOptions sport configs.
     *
     * @param string $id Registry ID
     * @return \Cake\Http\Response
     */
    public function view(string $id): Response
    {
        $message = 'Legacy stat registry entries are retired. '
            . 'Use sport configuration settings instead.';
        $this->Flash->warning(__($message));

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'sportsConfigs',
        ]);
    }

    /**
     * Redirect legacy add route to SiteOptions sport-config editor.
     *
     * @param string|null $sportId Optional sport ID to pre-select
     * @return \Cake\Http\Response
     */
    public function add(?string $sportId = null): Response
    {
        $target = [
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'editSportConfigs',
        ];

        if ($sportId !== null && trim($sportId) !== '') {
            $target[] = trim($sportId);
        }

        return $this->redirect($target);
    }

    /**
     * Redirect legacy edit route to SiteOptions sport configs.
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response
     */
    public function edit(string $id): Response
    {
        $message = 'Legacy stat registry entries are retired. '
            . 'Use sport configuration settings instead.';
        $this->Flash->warning(__($message));

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'sportsConfigs',
        ]);
    }

    /**
     * Redirect legacy delete route to SiteOptions sport configs.
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->Flash->success(__('Legacy stat registry configuration is retired and no longer required.'));

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'SiteOptions',
            'action' => 'sportsConfigs',
        ]);
    }
}
