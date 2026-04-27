<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * SportsAdminService
 *
 * Owns the full administrative CRUD slice for Sport records. Used exclusively
 * by Admin/SportsController so that controller remains a thin HTTP
 * orchestrator with no ORM access of its own.
 *
 * Config-management operations (configs, editConfigs, addConfig, deleteConfig,
 * resetConfigs) continue to delegate to SportConfigAdminService and are NOT
 * duplicated here.
 *
 * Responsibilities:
 * - Return index data (sports with Teams for record-count display).
 * - Provide the view entity (sport + Teams + formatted configs).
 * - Process add/edit/delete/bulk-delete form submissions.
 * - Handle the popup ajax-add flow, returning a structured response.
 *
 * Notes:
 * - Keep HTTP concerns (Flash, redirect, allowMethod) in the controller.
 * - Returned array keys are relied on by the controller and tests — do not
 *   rename them without updating call sites.
 * - ajaxAdd response keys ('success', 'message', 'newOption') must stay stable
 *   for frontend JS compatibility.
 *
 * @property \App\Model\Table\SportsTable $sportsTable
 */
class SportsAdminService
{
    /**
     * @var \App\Model\Table\SportsTable
     */
    private \App\Model\Table\SportsTable $sportsTable;

    /**
     * @param \App\Model\Table\SportsTable|null $sportsTable
     */
    public function __construct(?\App\Model\Table\SportsTable $sportsTable = null)
    {
        /** @var \App\Model\Table\SportsTable $table */
        $table = $sportsTable ?? TableRegistry::getTableLocator()->get('Sports');
        $this->sportsTable = $table;
    }

    /**
     * Return all sports with their Teams for the index listing.
     *
     * Teams are contained so templates can display associated record counts
     * in delete-confirmation dialogs.
     *
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getIndexData(): \Cake\Datasource\ResultSetInterface
    {
        return $this->sportsTable->find()->contain(['Teams'])->all();
    }

    /**
     * Return a single sport with Teams for the view action.
     *
     * @param string $id Sport ID
     * @return \App\Model\Entity\Sport
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getViewEntity(string $id): \App\Model\Entity\Sport
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->get($id, contain: ['Teams']);

        return $sport;
    }

    /**
     * Return a new empty Sport entity for the add form.
     *
     * @return \App\Model\Entity\Sport
     */
    public function newEntity(): \App\Model\Entity\Sport
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->newEmptyEntity();

        return $sport;
    }

    /**
     * Process an add form submission.
     *
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,sport:\App\Model\Entity\Sport}
     */
    public function add(array $data): array
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->newEmptyEntity();
        $sport = $this->sportsTable->patchEntity($sport, $data);

        if ($this->sportsTable->save($sport)) {
            return ['success' => true, 'sport' => $sport];
        }

        return ['success' => false, 'sport' => $sport];
    }

    /**
     * Return an existing Sport entity (with Teams) for the edit form.
     *
     * @param string $id Sport ID
     * @return \App\Model\Entity\Sport
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getEditEntity(string $id): \App\Model\Entity\Sport
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->get($id, contain: ['Teams']);

        return $sport;
    }

    /**
     * Process an edit form submission.
     *
     * @param string $id Sport ID
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,sport:\App\Model\Entity\Sport}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function edit(string $id, array $data): array
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->get($id, contain: ['Teams']);
        $sport = $this->sportsTable->patchEntity($sport, $data);

        if ($this->sportsTable->save($sport)) {
            return ['success' => true, 'sport' => $sport];
        }

        return ['success' => false, 'sport' => $sport];
    }

    /**
     * Delete a single sport.
     *
     * @param string $id Sport ID
     * @return bool
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function delete(string $id): bool
    {
        $sport = $this->sportsTable->get($id);

        return (bool)$this->sportsTable->delete($sport);
    }

    /**
     * Delete multiple sports by ID, silently skipping missing records.
     *
     * @param array<int|string> $ids Sanitized numeric IDs
     * @return int Count of successfully deleted records
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $sport = $this->sportsTable->get($id);
                if ($this->sportsTable->delete($sport)) {
                    $deleted++;
                }
            } catch (RecordNotFoundException $e) {
                continue;
            }
        }

        return $deleted;
    }

    /**
     * Persist a sport submitted via the ajax popup form.
     *
     * Returns a structured array compatible with the existing frontend
     * popup handler: success + message + newOption on success, or success +
     * errors on failure.
     *
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,message?:string,newOption?:array{value:int,text:string},errors?:array<string>}
     */
    public function createSportFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Sport $sport */
        $sport = $this->sportsTable->newEmptyEntity();
        $sport = $this->sportsTable->patchEntity($sport, $data);

        if ($this->sportsTable->save($sport)) {
            return [
                'success' => true,
                'message' => 'Sport has been added successfully.',
                'newOption' => [
                    'value' => (int)$sport->id,
                    'text' => (string)$sport->sport_name,
                ],
            ];
        }

        $errors = [];
        foreach ($sport->getErrors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = ucfirst((string)$field) . ': ' . $error;
            }
        }

        return [
            'success' => false,
            'errors' => $errors ?: ['Unable to save sport. Please try again.'],
        ];
    }
}
