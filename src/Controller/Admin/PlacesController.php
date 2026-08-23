<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PlaceAdminService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Exception;

/**
 * Places Admin Controller
 *
 * Handles place administration endpoints and delegates all data/persistence
 * orchestration to PlaceAdminService.
 *
 * Notes:
 * - Keep HTTP-only concerns (allowMethod, flash, redirects) in this class.
 * - Keep duplicate-place semantics consistent for both HTML and popup flows.
 * - Preserve JSON response keys used by frontend popup integrations.
 *
 * @property \App\Service\PlaceAdminService $placeAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\PlacesTable $Places
 * @property \App\Model\Table\SitesTable $Sites
 */
class PlacesController extends AppController
{
    /**
     * Service that owns place admin orchestration.
     *
     * @var \App\Service\PlaceAdminService
     */
    protected PlaceAdminService $placeAdminService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->placeAdminService = new PlaceAdminService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $unlockedActions = array_merge(
                $current,
                ['ajaxSearch', 'ajaxAdd', 'countriesLookup'],
            );
            $this->FormProtection->setConfig('unlockedActions', $unlockedActions);
        }
    }

    /**
     * Before filter - skip authentication for country lookup and form protection actions.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeFilter(EventInterface $event): void
    {
        $action = $this->request->getParam('action');

        // Skip authentication check for country lookup (external AJAX call)
        if ($action === 'countriesLookup') {
            return;
        }

        parent::beforeFilter($event);
    }

    /**
     * List places.
     */
    public function index(): void
    {
        $this->set('placeCount', $this->placeAdminService->getTotalCount());
    }

    /**
     * DataTables server-side JSON endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function datatables(): Response
    {
        $this->request->allowMethod(['get']);

        $orderColumn = 1;
        $orderDir = 'asc';
        $order = $this->request->getQuery('order');
        if (is_array($order) && !empty($order)) {
            $firstOrder = reset($order);
            if (is_array($firstOrder)) {
                $orderColumn = (int)($firstOrder['column'] ?? 1);
                $dir = strtolower((string)($firstOrder['dir'] ?? 'asc'));
                if (in_array($dir, ['asc', 'desc'], true)) {
                    $orderDir = $dir;
                }
            }
        }

        $result = $this->placeAdminService->buildDataTablesResponse([
            'draw' => (int)$this->request->getQuery('draw'),
            'start' => (int)$this->request->getQuery('start'),
            'length' => (int)$this->request->getQuery('length'),
            'searchValue' => trim((string)($this->request->getQuery('search')['value'] ?? '')),
            'orderColumn' => $orderColumn,
            'orderDir' => $orderDir,
        ], $this->request->getAttribute('identity'));

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'draw' => $result['draw'],
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $result['data'],
            ]));
    }

    /**
     * Add a new place.
     */
    public function add(): ?Response
    {
        $viewData = $this->placeAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->placeAdminService->saveNewPlace((array)$this->request->getData());
            $viewData['place'] = $result['place'];

            if ($result['success']) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }

            if ($result['duplicateViolation']) {
                $this->Flash->error('A place with that country, city, and state already exists.');
            } else {
                $this->Flash->error('The place could not be saved.');
            }
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Edit a place.
     *
     * @param string $id
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->placeAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->placeAdminService->saveExistingPlace($id, (array)$this->request->getData());
            $viewData['place'] = $result['place'];

            if ($result['success']) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }

            if ($result['duplicateViolation']) {
                $this->Flash->error('A place with that country, city, and state already exists.');
            } else {
                $this->Flash->error('The place could not be saved.');
            }
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Delete a place.
     *
     * @param string $id
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $identity = $this->request->getAttribute('identity');

        if ($this->placeAdminService->deletePlace($id, $identity)) {
            $this->Flash->success('The place has been deleted.');
        } else {
            $this->Flash->error('The place could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search places.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $payload = $this->placeAdminService->buildSearchResponse((string)$this->request->getQuery('q'), 30);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX add place from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $response = $this->placeAdminService->createPlaceFromPopup((array)$this->request->getData());

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

    /**
     * AJAX country lookup proxy - uses REST Countries v5 API.
     *
     * @return \Cake\Http\Response
     */
    public function countriesLookup(): Response
    {
        $this->request->allowMethod(['get']);
        $query = trim((string)$this->request->getQuery('q'));

        if (strlen($query) < 2) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([]));
        }

        try {
            // Use REST Countries v5 API with API key from config
            $apiKey = (string)(Configure::read('Api.RestCountries.key') ?? '');
            if (!$apiKey) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([]));
            }

            // Use the /name aggregate endpoint for country name searches
            $url = 'https://api.restcountries.com/countries/v5/name';
            $url .= '?q=' . urlencode($query);
            $url .= '&limit=20&response_fields=names.common,codes.alpha_3';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => 'Accept: application/json' . "\r\n" .
                        'Authorization: Bearer ' . $apiKey,
                ],
            ]);

            // Fetch data from REST Countries API, suppressing network warnings
            // as they're expected and handled by the null check below
            set_error_handler(static function () {
                return true;
            });
            $response = file_get_contents($url, false, $context);
            restore_error_handler();

            if ($response === false) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([]));
            }

            $payload = json_decode($response, true);
            if (!is_array($payload) || empty($payload['data']['objects'])) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([]));
            }

            // Transform the v5 response format to match frontend expectations
            $countries = array_map(function ($country) {
                return [
                    'name' => ['common' => $country['names']['common'] ?? ''],
                    'cca3' => strtoupper($country['codes']['alpha_3'] ?? ''),
                ];
            }, $payload['data']['objects']);

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(array_filter($countries, function ($c) {
                    return !empty($c['name']['common']) && !empty($c['cca3']);
                })));
        } catch (Exception $e) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([]));
        }
    }
}
