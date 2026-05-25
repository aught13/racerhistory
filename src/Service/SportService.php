<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Sport;
use Cake\ORM\TableRegistry;

/**
 * SportService
 *
 * Service layer for Sport entity CRUD and business logic.
 */
class SportService
{
    /**
     * Get a sport by ID.
     *
     * @param int $sportId Sport ID
     * @return \App\Model\Entity\Sport|null
     */
    public function getSportById(int $sportId): ?Sport
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');
        $sport = $sports->find()->where(['Sports.id' => $sportId])->first();

        return $sport instanceof Sport ? $sport : null;
    }

    /**
     * Get a friendly display label for a sport.
     *
     * @param int $sportId Sport ID
     * @return string
     */
    public function getDisplayLabel(int $sportId): string
    {
        $sport = $this->getSportById($sportId);
        if (!$sport) {
            return 'Sport #' . $sportId;
        }

        return $sport->sport_name ?? 'Sport #' . $sportId;
    }

    /**
     * Get all sports ordered alphabetically.
     *
     * @return array Array of Sport entities
     */
    public function getAllSports(): array
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');

        return $sports->find()
            ->orderBy(['Sports.sport_name' => 'ASC'])
            ->all()
            ->toArray();
    }

    /**
     * Create a new sport.
     *
     * @param array<string, mixed> $data Sport data
     * @return \App\Model\Entity\Sport|false
     */
    public function createSport(array $data): Sport|false
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');
        $sport = $sports->newEntity($data);

        return $sports->save($sport);
    }

    /**
     * Update an existing sport.
     *
     * @param int $sportId Sport ID
     * @param array<string, mixed> $data Sport data
     * @return \App\Model\Entity\Sport|false
     */
    public function updateSport(int $sportId, array $data): Sport|false
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');
        $sport = $sports->get($sportId);
        $sports->patchEntity($sport, $data);

        return $sports->save($sport);
    }

    /**
     * Delete a sport.
     *
     * @param int $sportId Sport ID
     * @return bool
     */
    public function deleteSport(int $sportId): bool
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');
        $sport = $sports->get($sportId);

        return (bool)$sports->delete($sport);
    }

    /**
     * Get sports formatted for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getSportsForSelect(): array
    {
        $sports = $this->getAllSports();
        $results = [];

        foreach ($sports as $sport) {
            if (!($sport instanceof Sport)) {
                continue;
            }
            $results[] = [
                'id' => $sport->id,
                'label' => $sport->sport_name,
            ];
        }

        return $results;
    }

    /**
     * Get sports as an associative list suitable for FormHelper selects.
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getSportsList(int $limit = 500): array
    {
        $sports = TableRegistry::getTableLocator()->get('Sports');

        $rows = $sports->find()
            ->orderBy(['Sports.sport_name' => 'ASC'])
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $sport) {
            if (!($sport instanceof Sport)) {
                continue;
            }
            $list[(int)$sport->id] = (string)($sport->sport_name ?? '');
        }

        return $list;
    }
}
