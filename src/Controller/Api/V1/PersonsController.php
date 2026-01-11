<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\PersonService;

class PersonsController extends AppController
{
    private PersonService $personService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->personService = new PersonService();
    }

    /**
     * List persons (supports ?q= search).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $limit = $this->getLimit(50, 200);
        $q = trim((string)$this->getRequest()->getQuery('q', ''));

        if ($q !== '') {
            $results = $this->personService->searchPersons($q, $limit);
            $this->respond([
                'data' => $results,
                'meta' => [
                    'count' => count($results),
                    'q' => $q,
                    'limit' => $limit,
                ],
            ]);

            return;
        }

        $list = $this->personService->getPersonsList($limit);
        $data = [];
        foreach ($list as $id => $label) {
            $data[] = ['id' => (int)$id, 'label' => $label];
        }

        $this->respond([
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Get a single person.
     */
    public function view(int $id): void
    {
        $this->request->allowMethod(['get']);

        $person = $this->personService->getPersonById($id);
        if (!$person) {
            $this->respondError('Person not found', 404);

            return;
        }

        $first = (string)($person->first ?? '');
        $last = (string)($person->last ?? '');
        $display = (string)($person->display ?? '');

        $label = $display !== ''
            ? $display
            : (trim($first . ' ' . $last) !== '' ? trim($first . ' ' . $last) : (string)$person->getLabel());

        $this->respond([
            'data' => [
                'id' => (int)$person->id,
                'first' => $person->first,
                'last' => $person->last,
                'full' => $person->full ?? null,
                'display' => $person->display ?? null,
                'label' => $label,
            ],
        ]);
    }
}
