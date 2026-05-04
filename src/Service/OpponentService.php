<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Opponent;
use Cake\ORM\TableRegistry;

/**
 * OpponentService
 *
 * Service layer for Opponent entity CRUD and business logic.
 */
class OpponentService
{
    /**
     * Get an opponent by ID.
     *
     * @param int $opponentId Opponent ID
     * @return \App\Model\Entity\Opponent|null
     */
    public function getOpponentById(int $opponentId): ?Opponent
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');

        return $opponents->find()->where(['Opponents.id' => $opponentId])->first();
    }

    /**
     * Get a friendly display label for an opponent.
     *
     * @param int $opponentId Opponent ID
     * @return string
     */
    public function getDisplayLabel(int $opponentId): string
    {
        $opponent = $this->getOpponentById($opponentId);
        if (!$opponent) {
            return 'Opponent #' . $opponentId;
        }

        return $opponent->opponent_name ?? 'Opponent #' . $opponentId;
    }

    /**
     * Search opponents by name or short name.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Array of Opponent entities
     */
    public function searchOpponents(string $query, int $limit = 20): array
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');

        if (trim($query) === '') {
            return [];
        }

        return $opponents->find()
            ->where([
                'OR' => [
                    'Opponents.opponent_name LIKE' => "%{$query}%",
                    'Opponents.opponent_short LIKE' => "%{$query}%",
                ],
            ])
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Get all opponents ordered alphabetically.
     *
     * @param int $limit Result limit
     * @return array Array of Opponent entities
     */
    public function getAllOpponents(int $limit = 500): array
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');

        return $opponents->find()
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Create a new opponent.
     *
     * @param array<string, mixed> $data Opponent data
     * @return \App\Model\Entity\Opponent|false
     */
    public function createOpponent(array $data): Opponent|false
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        $opponent = $opponents->newEntity($data);

        return $opponents->save($opponent);
    }

    /**
     * Update an existing opponent.
     *
     * @param int $opponentId Opponent ID
     * @param array<string, mixed> $data Opponent data
     * @return \App\Model\Entity\Opponent|false
     */
    public function updateOpponent(int $opponentId, array $data): Opponent|false
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        $opponent = $opponents->get($opponentId);
        $opponents->patchEntity($opponent, $data);

        return $opponents->save($opponent);
    }

    /**
     * Delete an opponent.
     *
     * @param int $opponentId Opponent ID
     * @return bool
     */
    public function deleteOpponent(int $opponentId): bool
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        $opponent = $opponents->get($opponentId);

        return (bool)$opponents->delete($opponent);
    }

    /**
     * Get opponents formatted for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getOpponentsForSelect(): array
    {
        $opponents = $this->getAllOpponents();
        $results = [];

        foreach ($opponents as $opponent) {
            $results[] = [
                'id' => $opponent->id,
                'label' => $opponent->opponent_name,
            ];
        }

        return $results;
    }

    /**
     * Get opponents as an associative list suitable for FormHelper selects.
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getOpponentsList(int $limit = 500): array
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');

        $rows = $opponents->find()
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $opponent) {
            $list[(int)$opponent->id] = (string)($opponent->opponent_name ?? '');
        }

        return $list;
    }
}
