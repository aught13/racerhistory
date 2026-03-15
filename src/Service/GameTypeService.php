<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * GameTypeService
 *
 * Service layer for GameType entity CRUD and list generation.
 */
class GameTypeService
{
    /**
     * Get a game type by ID.
     *
     * @param int $gameTypeId Game type ID
     * @return \App\Model\Entity\GameType|null
     */
    public function getGameTypeById(int $gameTypeId): ?\App\Model\Entity\GameType
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');

        return $gameTypes->find()->where(['GameTypes.id' => $gameTypeId])->first();
    }

    /**
     * Get a friendly display label for a game type.
     *
     * @param int $gameTypeId Game type ID
     * @return string
     */
    public function getDisplayLabel(int $gameTypeId): string
    {
        $gameType = $this->getGameTypeById($gameTypeId);
        if (!$gameType) {
            return 'Game Type #' . $gameTypeId;
        }

        return $gameType->game_type_name ?? 'Game Type #' . $gameTypeId;
    }

    /**
     * Get all game types ordered alphabetically.
     *
     * @return array<int,\App\Model\Entity\GameType>
     */
    public function getAllGameTypes(): array
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');

        return $gameTypes->find()
            ->orderBy(['GameTypes.game_type_name' => 'ASC'])
            ->all()
            ->toArray();
    }

    /**
     * Create a new game type.
     *
     * @param array<string,mixed> $data Game type data
     * @return \App\Model\Entity\GameType|false
     */
    public function createGameType(array $data): \App\Model\Entity\GameType|false
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');
        $gameType = $gameTypes->newEntity($data);

        return $gameTypes->save($gameType);
    }

    /**
     * Update an existing game type.
     *
     * @param int $gameTypeId Game type ID
     * @param array<string,mixed> $data Updated game type data
     * @return \App\Model\Entity\GameType|false
     */
    public function updateGameType(int $gameTypeId, array $data): \App\Model\Entity\GameType|false
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');
        $gameType = $gameTypes->get($gameTypeId);
        $gameTypes->patchEntity($gameType, $data);

        return $gameTypes->save($gameType);
    }

    /**
     * Delete a game type.
     *
     * @param int $gameTypeId Game type ID
     * @return bool
     */
    public function deleteGameType(int $gameTypeId): bool
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');
        $gameType = $gameTypes->get($gameTypeId);

        return (bool)$gameTypes->delete($gameType);
    }

    /**
     * Get game types as an associative list suitable for FormHelper selects.
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getGameTypesList(int $limit = 500): array
    {
        $gameTypes = TableRegistry::getTableLocator()->get('GameTypes');

        $rows = $gameTypes->find()
            ->orderBy(['GameTypes.game_type_name' => 'ASC'])
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $gt) {
            $list[(int)$gt->id] = (string)($gt->game_type_name ?? '');
        }

        return $list;
    }
}
