<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

/**
 * GameTypeAdminService
 *
 * Owns administrative orchestration for game type management, including
 * standard CRUD persistence, association-guarded deletes, popup responses, and
 * search payload shaping.
 *
 * Notes:
 * - Keep delete guard behavior stable because tests rely on it.
 * - Preserve response shape for popup integrations.
 * - Keep controller logic transport-only.
 */
class GameTypeAdminService
{
    /**
     * Return index page data.
     *
     * @return array{gameTypes:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $gameTypes = $this->getGameTypesTable()->find()->all();

        return compact('gameTypes');
    }

    /**
     * Return add form data.
     *
     * @return array{gameType:\App\Model\Entity\GameType}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\GameType $gameType */
        $gameType = $this->getGameTypesTable()->newEmptyEntity();

        return compact('gameType');
    }

    /**
     * Save new game type.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,gameType:\App\Model\Entity\GameType}
     */
    public function saveNewGameType(array $data): array
    {
        /** @var \App\Model\Entity\GameType $gameType */
        $gameType = $this->getGameTypesTable()->newEmptyEntity();
        $gameType = $this->getGameTypesTable()->patchEntity($gameType, $data);
        $success = (bool)$this->getGameTypesTable()->save($gameType);

        return compact('success', 'gameType');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Game type identifier
     * @return array{gameType:\App\Model\Entity\GameType}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\GameType $gameType */
        $gameType = $this->getGameTypesTable()->get($id);

        return compact('gameType');
    }

    /**
     * Save existing game type.
     *
     * @param string|int $id Game type identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,gameType:\App\Model\Entity\GameType}
     */
    public function saveExistingGameType(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\GameType $gameType */
        $gameType = $this->getGameTypesTable()->get($id);
        $gameType = $this->getGameTypesTable()->patchEntity($gameType, $data);
        $success = (bool)$this->getGameTypesTable()->save($gameType);

        return compact('success', 'gameType');
    }

    /**
     * Delete a game type, guarding against existing games.
     *
     * @param string|int $id Game type identifier
     * @return array{blocked:bool,deleted:bool}
     */
    public function deleteGameType(int|string $id): array
    {
        $entity = $this->getGameTypesTable()->get($id);
        if ($this->getGameTypesTable()->Games->exists(['game_type_id' => $entity->id])) {
            return [
                'blocked' => true,
                'deleted' => false,
            ];
        }

        return [
            'blocked' => false,
            'deleted' => (bool)$this->getGameTypesTable()->delete($entity),
        ];
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

        $gameTypes = (new GameTypeService())->searchGameTypes($query, $limit);
        $results = [];
        foreach ($gameTypes as $gameType) {
            $results[] = [
                'id' => $gameType->id,
                'game_type_name' => $gameType->game_type_name,
                'abr' => $gameType->abr,
                'post' => $gameType->post,
                'conf' => $gameType->conf,
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Save a new game type via popup form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createGameTypeFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\GameType $gameType */
        $gameType = $this->getGameTypesTable()->newEmptyEntity();
        $gameType = $this->getGameTypesTable()->patchEntity($gameType, $data);

        if ($this->getGameTypesTable()->save($gameType)) {
            return [
                'success' => true,
                'message' => 'The game type has been saved.',
                'newOption' => [
                    'value' => $gameType->id,
                    'text' => $gameType->game_type_name,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($gameType) ?: ['Unable to save game type.'],
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
     * @return \App\Model\Table\GameTypesTable
     */
    private function getGameTypesTable(): \App\Model\Table\GameTypesTable
    {
        /** @var \App\Model\Table\GameTypesTable $table */
        $table = TableRegistry::getTableLocator()->get('GameTypes');

        return $table;
    }
}
