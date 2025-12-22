<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * TeamSeasonRosterService
 *
 * Service layer for TeamSeasonRoster entity operations and display data generation.
 * Coordinates with PersonService and TeamSeasonService for associated data.
 */
class TeamSeasonRosterService
{
    private PersonService $personService;
    private TeamSeasonService $teamSeasonService;

    /**
     * Constructor.
     *
     * @param \App\Service\PersonService|null $personService Person service instance
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Team season service instance
     */
    public function __construct(
        ?PersonService $personService = null,
        ?TeamSeasonService $teamSeasonService = null,
    ) {
        $this->personService = $personService ?? new PersonService();
        $this->teamSeasonService = $teamSeasonService ?? new TeamSeasonService();
    }

    /**
     * Get a roster entry by ID.
     *
     * @param int $rosterId
     * @return \App\Model\Entity\TeamSeasonRosters|null
     */
    public function getRosterById(int $rosterId): ?\App\Model\Entity\TeamSeasonRosters
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $rosters->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.id' => $rosterId])
            ->first();
    }

    /**
     * Get a friendly display label for a roster entry.
     * Delegates to TeamSeasonService to get sport+season label.
     *
     * @param int $rosterId
     * @return string
     */
    public function getDisplayLabel(int $rosterId): string
    {
        $roster = $this->getRosterById($rosterId);
        if (!$roster) {
            return 'Roster #' . $rosterId;
        }

        // Use team season service to get sport-prefixed label
        return $this->teamSeasonService->getSportDisplayLabel($roster->team_season_id);
    }

    /**
     * Get roster options for a person (for dropdown/select UI).
     *
     * @param int $personId
     * @return array Array of [{id, label}, ...]
     */
    public function getRostersForPerson(int $personId): array
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
        $results = [];

        $rosterRecords = $rosters->find()
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->all();

        foreach ($rosterRecords as $roster) {
            $results[] = [
                'id' => $roster->id,
                'label' => $this->getDisplayLabel($roster->id),
            ];
        }

        return $results;
    }

    /**
     * Get combined display data for roster: person name + team season.
     *
     * @param int $rosterId
     * @return array {person_id, person_label, team_season_id, team_season_label}
     */
    public function getRosterDisplayData(int $rosterId): array
    {
        $roster = $this->getRosterById($rosterId);
        if (!$roster) {
            return [
                'person_id' => null,
                'person_label' => 'Unknown',
                'team_season_id' => null,
                'team_season_label' => 'Unknown',
            ];
        }

        // Format the roster label: {Team Name} ({Season Start-End}) [Roster]
        // Use brackets to clearly distinguish from Team Season tags
        $teamSeason = $roster->team_season ?? null;
        $rosterLabel = 'Unknown';
        if ($teamSeason) {
            $teamName = $teamSeason->team->team_name ?? 'Team';
            $seasonLabel = '';
            if ($teamSeason->season) {
                $start = $teamSeason->season->start ?? null;
                $end = $teamSeason->season->end ?? null;
                if ($start && $end && $start != $end) {
                    $seasonLabel = " ({$start}-{$end})";
                } elseif ($start) {
                    $seasonLabel = " ({$start})";
                }
            }
            $rosterLabel = $teamName . $seasonLabel . ' [Roster]';
        }

        return [
            'person_id' => $roster->person_id,
            'person_label' => $this->personService->getDisplayLabel($roster->person_id),
            'team_season_id' => $roster->team_season_id,
            'team_season_label' => $rosterLabel,
        ];
    }

    /**
     * Get all roster entries.
     *
     * @return array
     */
    public function getAllRosters(): array
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $rosters->find()
            ->all()
            ->toArray();
    }

    /**
     * Create a new roster entry.
     *
     * @param array $data Roster data (person_id, team_season_id, jersey_number, etc.)
     * @return \App\Model\Entity\TeamSeasonRosters|false
     */
    public function createRoster(array $data): \App\Model\Entity\TeamSeasonRosters|false
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
        $roster = $rosters->newEntity($data);

        return $rosters->save($roster) ? $roster : false;
    }

    /**
     * Update an existing roster entry.
     *
     * @param int $rosterId
     * @param array $data Updated roster data
     * @return \App\Model\Entity\TeamSeasonRosters|false
     */
    public function updateRoster(int $rosterId, array $data): \App\Model\Entity\TeamSeasonRosters|false
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
        $roster = $rosters->get($rosterId);

        $roster = $rosters->patchEntity($roster, $data);

        return $rosters->save($roster) ? $roster : false;
    }

    /**
     * Delete a roster entry.
     *
     * @param int $rosterId
     * @return bool
     */
    public function deleteRoster(int $rosterId): bool
    {
        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
        $roster = $rosters->get($rosterId);

        return $rosters->delete($roster);
    }

    /**
     * Get roster entries for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getRostersForSelect(): array
    {
        $rosters = $this->getAllRosters();
        $results = [];

        foreach ($rosters as $roster) {
            $results[] = [
                'id' => $roster->id,
                'label' => $this->getDisplayLabel($roster->id),
            ];
        }

        return $results;
    }
}
