<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * SeasonService
 *
 * Service layer for Season entity CRUD and business logic.
 * Encapsulates all season-related operations independent of the framework.
 */
class SeasonService
{
    /**
     * Get a season by ID.
     *
     * @param int $seasonId Season ID
     * @return \App\Model\Entity\Season|null
     */
    public function getSeasonById(int $seasonId): ?\App\Model\Entity\Season
    {
        $seasons = TableRegistry::getTableLocator()->get('Seasons');

        return $seasons->find()->where(['Seasons.id' => $seasonId])->first();
    }

    /**
     * Get a friendly display label for a season.
     * Format: "2023-2024" or "2024"
     *
     * @param int $seasonId Season ID
     * @return string
     */
    public function getDisplayLabel(int $seasonId): string
    {
        $season = $this->getSeasonById($seasonId);
        if (!$season) {
            return 'Season #' . $seasonId;
        }

        $start = $season->start ?? null;
        $end = $season->end ?? null;

        if ($start && $end && $start != $end) {
            return "{$start}-{$end}";
        } elseif ($start) {
            return (string)$start;
        }

        return 'Season #' . $seasonId;
    }

    /**
     * Get all seasons ordered by start year descending.
     *
     * @param int $limit Result limit
     * @return array Array of Season entities
     */
    public function getAllSeasons(int $limit = 100): array
    {
        $seasons = TableRegistry::getTableLocator()->get('Seasons');

        return $seasons->find()
            ->orderBy(['Seasons.start' => 'DESC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Create a new season.
     *
     * @param array<string, mixed> $data Season data
     * @return \App\Model\Entity\Season|false
     */
    public function createSeason(array $data): \App\Model\Entity\Season|false
    {
        $seasons = TableRegistry::getTableLocator()->get('Seasons');
        $season = $seasons->newEntity($data);

        return $seasons->save($season);
    }

    /**
     * Update an existing season.
     *
     * @param int $seasonId Season ID
     * @param array<string, mixed> $data Season data
     * @return \App\Model\Entity\Season|false
     */
    public function updateSeason(int $seasonId, array $data): \App\Model\Entity\Season|false
    {
        $seasons = TableRegistry::getTableLocator()->get('Seasons');
        $season = $seasons->get($seasonId);
        $seasons->patchEntity($season, $data);

        return $seasons->save($season);
    }

    /**
     * Delete a season.
     *
     * @param int $seasonId Season ID
     * @return bool
     */
    public function deleteSeason(int $seasonId): bool
    {
        $seasons = TableRegistry::getTableLocator()->get('Seasons');
        $season = $seasons->get($seasonId);

        return (bool)$seasons->delete($season);
    }

    /**
     * Get seasons formatted for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getSeasonsForSelect(): array
    {
        $seasons = $this->getAllSeasons();
        $results = [];

        foreach ($seasons as $season) {
            $results[] = [
                'id' => $season->id,
                'label' => $this->getDisplayLabel($season->id),
            ];
        }

        return $results;
    }
}
