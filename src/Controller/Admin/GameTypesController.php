<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameTypeAdminService;
use Cake\Http\Response;

/**
 * GameTypes Admin Controller
 *
 * Handles admin game-type endpoints and delegates all business and persistence
 * orchestration to GameTypeAdminService.
 *
 * Notes:
 * - Keep HTTP concerns (allowMethod, flash, redirects) in this controller.
 * - Keep delete guard semantics unchanged to avoid behavioral regressions.
 * - Preserve popup/autocomplete JSON key names.
 *
 * @property \App\Service\GameTypeAdminService $gameTypeAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\GameTypesTable $GameTypes
 */
class GameTypesController extends AppController
{
    /**
     * Service that owns game-type admin orchestration.
     *
     * @var \App\Service\GameTypeAdminService
     */
    protected GameTypeAdminService $gameTypeAdminService;

    /**
     * Initialize controller and adjust FormProtection unlocked actions.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->gameTypeAdminService = new GameTypeAdminService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $unlocked = array_merge($current, ['delete', 'ajaxSearch', 'ajaxAdd']);
            $this->FormProtection->setConfig('unlockedActions', $unlocked);
        }
    }

    /**
     * List game types.
     */
    public function index(): void
    {
        $this->set($this->gameTypeAdminService->getIndexData());
    }

    /**
     * Add a new game type.
     */
    public function add(): ?Response
    {
        $viewData = $this->gameTypeAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->gameTypeAdminService->saveNewGameType((array)$this->request->getData());
            $viewData['gameType'] = $result['gameType'];

            if ($result['success']) {
                $this->Flash->success('The game type has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The game type could not be saved.');
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Edit a game type.
     *
     * @param string $id
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->gameTypeAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->gameTypeAdminService->saveExistingGameType($id, (array)$this->request->getData());
            $viewData['gameType'] = $result['gameType'];

            if ($result['success']) {
                $this->Flash->success('The game type has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The game type could not be saved.');
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Delete a game type.
     *
     * @param string $id
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $result = $this->gameTypeAdminService->deleteGameType($id);

        if ($result['blocked']) {
            $this->Flash->error('This game type cannot be deleted because games are associated with it.');

            return $this->redirect(['action' => 'index']);
        }

        if ($result['deleted']) {
            $this->Flash->success('The game type has been deleted.');
        } else {
            $this->Flash->error('The game type could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search game types.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $payload = $this->gameTypeAdminService->buildSearchResponse((string)$this->request->getQuery('q'), 30);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX add game type from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $response = $this->gameTypeAdminService->createGameTypeFromPopup((array)$this->request->getData());

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($response));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => ['Invalid request method.'],
            ]));
    }
}
