<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Person;
use App\Model\Entity\TeamSeasonRosters;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * PersonService
 *
 * Service layer for Person entity operations and display data generation.
 */
class PersonService
{
    private TeamSportContextService $teamSportContextService;

    /**
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Sport compatibility resolver.
     */
    public function __construct(?TeamSportContextService $teamSportContextService = null)
    {
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
    }

    /**
     * Get a person by ID.
     *
     * @param int $personId
     * @return \App\Model\Entity\Person|null
     */
    public function getPersonById(int $personId): ?Person
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');
        $person = $persons->find()->where(['Persons.id' => $personId])->first();

        return $person instanceof Person ? $person : null;
    }

    /**
     * Restrict a People directory query to people with a roster in the selected scope.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Person> $query Persons query.
     * @param string|null $sportKey Canonical sport key.
     * @param int|null $teamId Team identifier.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Person>
     */
    public function applyPublicRosterScope(
        SelectQuery $query,
        ?string $sportKey,
        ?int $teamId,
    ): SelectQuery {
        if ($sportKey === null && $teamId === null) {
            return $query;
        }

        $rosterTable = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
        $personIds = $rosterTable->find()
            ->select(['TeamSeasonRosters.person_id'])
            ->distinct(['TeamSeasonRosters.person_id']);
        $personIds = $this->applyRosterScope($personIds, $sportKey, $teamId);

        return $query->where(['Persons.id IN' => $personIds]);
    }

    /**
     * Get roster history for selected people, optionally limited to a public scope.
     *
     * @param array<int> $personIds Person identifiers.
     * @param string|null $sportKey Canonical sport key.
     * @param int|null $teamId Team identifier.
     * @return array<int,\App\Model\Entity\TeamSeasonRosters>
     */
    public function getRosterEntriesForPeople(
        array $personIds,
        ?string $sportKey = null,
        ?int $teamId = null,
    ): array {
        $normalizedPersonIds = array_values(array_unique(array_filter(
            array_map('intval', $personIds),
            static fn(int $personId): bool => $personId > 0,
        )));
        if ($normalizedPersonIds === []) {
            return [];
        }

        $rosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters')->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.person_id IN' => $normalizedPersonIds]);
        $rows = $this->applyRosterScope($rosters, $sportKey, $teamId)
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        /** @var array<int,\App\Model\Entity\TeamSeasonRosters> $entries */
        $entries = array_values(array_filter(
            $rows,
            static fn($entry): bool => $entry instanceof TeamSeasonRosters,
        ));

        return $entries;
    }

    /**
     * Get a friendly display label for a person.
     * Uses the Person entity's virtual label field.
     *
     * @param int $personId
     * @return string
     */
    public function getDisplayLabel(int $personId): string
    {
        $person = $this->getPersonById($personId);
        if (!$person) {
            return 'Person #' . $personId;
        }

        // Try accessing display field directly first
        if (!empty($person->display)) {
            return $person->display;
        }

        // Try building from first/last
        $first = $person->first ?? '';
        $last = $person->last ?? '';
        $name = trim($first . ' ' . $last);
        if ($name) {
            return $name;
        }

        // Try the virtual label field
        return $person->getLabel();
    }

    /**
     * Search persons by name with optional disambiguation data.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Array of [{id, label, disambiguate}, ...]
     */
    public function searchPersons(string $query, int $limit = 20): array
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');
        $results = [];

        $query = trim($query);
        if ($query === '') {
            return $results;
        }

        $personResults = $persons->find()
            ->where([
                'OR' => [
                    'Persons.first LIKE' => "%{$query}%",
                    'Persons.last LIKE' => "%{$query}%",
                    'Persons.display LIKE' => "%{$query}%",
                ],
            ])
            ->limit($limit)
            ->all();

        foreach ($personResults as $person) {
            if (!($person instanceof Person)) {
                continue;
            }
            $label = (string)($person->display ?? '');
            if ($label === '') {
                $label = trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? ''));
            }
            if ($label === '') {
                $label = 'Person #' . (string)$person->id;
            }

            $results[] = [
                'id' => $person->id,
                'label' => $label,
                'disambiguate' => null, // Can be enhanced with roster/team info
            ];
        }

        return $results;
    }

    /**
     * Person lookup used by image tagging UI.
     * Includes a best-effort "latest roster" context to help disambiguate
     * identical names, while keeping the base label consistent.
     *
     * @param string $query
     * @param int $limit
     */
    public function searchPersonsForImageTagging(string $query, int $limit = 25): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $personsTable = TableRegistry::getTableLocator()->get('Persons');
        $teamSeasonRostersTable = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        $like = '%' . str_replace('%', '\\%', $query) . '%';

        $rows = $personsTable->find()
            ->select(['id', 'first', 'last', 'full', 'display'])
            ->where([
                'OR' => [
                    ['Persons.first LIKE' => $like],
                    ['Persons.last LIKE' => $like],
                    ['Persons.full LIKE' => $like],
                    ['Persons.display LIKE' => $like],
                ],
            ])
            ->orderBy(['Persons.last' => 'ASC', 'Persons.first' => 'ASC'])
            ->limit($limit)
            ->all();

        $out = [];
        foreach ($rows as $person) {
            if (!($person instanceof Person)) {
                continue;
            }
            $base = trim((string)($person->display ?? ''))
                ?: trim((string)($person->full ?? ''))
                ?: trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? ''));

            $extra = '';
            $latestRoster = $teamSeasonRostersTable->find()
                ->where(['TeamSeasonRosters.person_id' => $person->id])
                ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
                ->orderBy(['Seasons.start' => 'DESC', 'TeamSeasonRosters.id' => 'DESC'])
                ->limit(1)
                ->first();

            if ($latestRoster) {
                $teamSeason = $latestRoster->team_season ?? null;
                if ($teamSeason) {
                    $teamName = $teamSeason->team?->getDisplayName();
                    $season = $teamSeason->season ?? null;
                    if ($teamName) {
                        $seasonLabel = '';
                        if ($season) {
                            $start = $season->start ?? null;
                            $end = $season->end ?? null;
                            if ($start && $end && $start != $end) {
                                $seasonLabel = " {$start}-{$end}";
                            } elseif ($start) {
                                $seasonLabel = " {$start}";
                            }
                        }
                        $extra = trim($teamName . $seasonLabel);
                    }
                }
            }

            $label = $base . ($extra !== '' ? ' — ' . $extra : '');
            $out[] = ['id' => $person->id, 'label' => $label];
        }

        return $out;
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query Roster query.
     * @param string|null $sportKey Canonical sport key.
     * @param int|null $teamId Team identifier.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function applyRosterScope(SelectQuery $query, ?string $sportKey, ?int $teamId): SelectQuery
    {
        $conditions = [];
        if ($sportKey !== null) {
            $conditions = $this->teamSportContextService->buildSportFilterConditions($sportKey);
            if ($conditions === []) {
                return $query->where(['Teams.id' => -1]);
            }
        }
        if ($teamId !== null) {
            $conditions['Teams.id'] = $teamId;
        }

        if ($conditions === []) {
            return $query;
        }

        return $query
            ->matching('TeamSeasons.Seasons')
            ->matching('TeamSeasons.Teams', static function ($teamsQuery) use ($conditions) {
                return $teamsQuery->where($conditions);
            });
    }

    /**
     * Get all persons ordered by display name.
     *
     * @return array
     */
    public function getAllPersons(): array
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');

        $rows = $persons->find()
            ->orderBy(['Persons.last' => 'ASC', 'Persons.first' => 'ASC'])
            ->all()
            ->toArray();

        /** @var array<int,\App\Model\Entity\Person> $result */
        $result = array_values(array_filter($rows, static fn($row): bool => $row instanceof Person));

        return $result;
    }

    /**
     * Create a new person.
     *
     * @param array $data Person data (first, last, display, etc.)
     * @return \App\Model\Entity\Person|false
     */
    public function createPerson(array $data): Person|false
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');
        $person = $persons->newEntity($data);

        return $persons->save($person) ? $person : false;
    }

    /**
     * Update an existing person.
     *
     * @param int $personId
     * @param array $data Updated person data
     * @return \App\Model\Entity\Person|false
     */
    public function updatePerson(int $personId, array $data): Person|false
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');
        $person = $persons->get($personId);

        $person = $persons->patchEntity($person, $data);

        return $persons->save($person) ? $person : false;
    }

    /**
     * Delete a person.
     *
     * @param int $personId
     * @return bool
     */
    public function deletePerson(int $personId): bool
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');
        $person = $persons->get($personId);

        return $persons->delete($person);
    }

    /**
     * Get persons for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getPersonsForSelect(): array
    {
        $persons = $this->getAllPersons();
        $results = [];

        foreach ($persons as $person) {
            if (!($person instanceof Person)) {
                continue;
            }
            $results[] = [
                'id' => $person->id,
                'label' => $person->label ?? trim(($person->first ?? '') . ' ' . ($person->last ?? '')),
            ];
        }

        return $results;
    }

    /**
     * Get persons as an associative list suitable for FormHelper selects.
     *
     * Optionally ensures a specific person ID is present even if outside the limit.
     *
     * @param int $limit
     * @param int|null $ensurePersonId
     * @return array<int,string>
     */
    public function getPersonsList(int $limit = 200, ?int $ensurePersonId = null): array
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');

        /** @var array<int,string> $list */
        $list = $persons->find('list', limit: $limit)->all()->toArray();

        if ($ensurePersonId !== null && $ensurePersonId > 0 && !isset($list[$ensurePersonId])) {
            $list[$ensurePersonId] = $this->getDisplayLabel($ensurePersonId);
        }

        return $list;
    }
}
