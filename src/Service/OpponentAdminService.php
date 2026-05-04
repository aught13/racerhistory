<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\OpponentsTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

/**
 * OpponentAdminService
 *
 * Owns the administrative opponent management slice used by the admin
 * controller: list queries, add/edit persistence, popup payload generation, and
 * AJAX search result shaping.
 *
 * Notes:
 * - Keep HTTP concerns (Flash/Redirect/allowMethod) in controllers.
 * - Preserve response keys used by popup and search JavaScript integrations.
 * - Reuse OpponentService/PlaceService helpers where possible.
 */
class OpponentAdminService
{
    /**
     * Return index page data.
     *
     * @return array{opponents:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $opponents = $this->getOpponentsTable()->find()
            ->contain(['Places'])
            ->all();

        return compact('opponents');
    }

    /**
     * Return add form data.
     *
     * @return array{opponent:\App\Model\Entity\Opponent,places:array<int,string>,opponentsList:\Cake\Datasource\ResultSetInterface}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $places = (new PlaceService())->getPlacesList();
        $opponentsList = $this->getOpponentsTable()->find('list')
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        return compact('opponent', 'places', 'opponentsList');
    }

    /**
     * Save new opponent.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,opponent:\App\Model\Entity\Opponent}
     */
    public function saveNewOpponent(array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);
        $success = (bool)$this->getOpponentsTable()->save($opponent);

        return compact('success', 'opponent');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Opponent identifier
     * @return array{opponent:\App\Model\Entity\Opponent,places:array<int,string>,opponentsList:\Cake\Datasource\ResultSetInterface}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->get($id);
        $places = (new PlaceService())->getPlacesList();
        $opponentsList = $this->getOpponentsTable()->find('list')
            ->where(['Opponents.id !=' => $id])
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        return compact('opponent', 'places', 'opponentsList');
    }

    /**
     * Save existing opponent.
     *
     * @param string|int $id Opponent identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,opponent:\App\Model\Entity\Opponent}
     */
    public function saveExistingOpponent(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->get($id);
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);
        $success = (bool)$this->getOpponentsTable()->save($opponent);

        return compact('success', 'opponent');
    }

    /**
     * Delete an opponent.
     *
     * @param string|int $id Opponent identifier
     * @return bool
     */
    public function deleteOpponent(int|string $id): bool
    {
        $opponent = $this->getOpponentsTable()->get($id);

        return (bool)$this->getOpponentsTable()->delete($opponent);
    }

    /**
     * Return JSON-ready search response payload.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array{success:bool,results:array<int,array<string,mixed>>}
     */
    public function buildSearchResponse(string $query, int $limit = 30): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'success' => true,
                'results' => [],
            ];
        }

        $opponents = (new OpponentService())->searchOpponents($query, $limit);
        $results = [];
        foreach ($opponents as $opponent) {
            $results[] = [
                'id' => $opponent->id,
                'opponent_name' => $opponent->opponent_name,
                'opponent_short' => $opponent->opponent_short,
                'opponent_abbr' => $opponent->opponent_abbr,
                'opponent_mascot' => $opponent->opponent_mascot,
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Save a new opponent via popup form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createOpponentFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);

        if ($this->getOpponentsTable()->save($opponent)) {
            return [
                'success' => true,
                'message' => 'The opponent has been saved.',
                'newOption' => [
                    'value' => $opponent->id,
                    'text' => $opponent->opponent_name,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($opponent) ?: ['Unable to save opponent.'],
        ];
    }

    /**
     * Convert validation errors to user-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity with validation errors
     * @return array<int,string>
     */
    private function collectValidationErrors(EntityInterface $entity): array
    {
        $errors = [];
        foreach ($entity->getErrors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = ucfirst((string)$field) . ': ' . (string)$error;
            }
        }

        return $errors;
    }

    /**
     * @return \App\Model\Table\OpponentsTable
     */
    private function getOpponentsTable(): OpponentsTable
    {
        /** @var \App\Model\Table\OpponentsTable $table */
        $table = TableRegistry::getTableLocator()->get('Opponents');

        return $table;
    }
}
