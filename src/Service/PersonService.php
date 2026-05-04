<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Person;
use Cake\ORM\TableRegistry;

/**
 * PersonService
 *
 * Service layer for Person entity operations and display data generation.
 */
class PersonService
{
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

        return $person;
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
            $base = trim((string)($person->display ?? ''))
                ?: trim((string)($person->full ?? ''))
                ?: trim((string)($person->first ?? '') . ' ' . (string)($person->last ?? ''));

            $extra = '';
            $latestRoster = $teamSeasonRostersTable->find()
                ->select(['TeamSeasonRosters.id', 'TeamSeasonRosters.team_season_id'])
                ->where(['TeamSeasonRosters.person_id' => $person->id])
                ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
                ->orderBy(['Seasons.start' => 'DESC', 'TeamSeasonRosters.id' => 'DESC'])
                ->limit(1)
                ->first();

            if ($latestRoster) {
                $teamSeason = $latestRoster->team_season ?? null;
                if ($teamSeason) {
                    $teamName = $teamSeason->team->team_name ?? null;
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
     * Get all persons ordered by display name.
     *
     * @return array
     */
    public function getAllPersons(): array
    {
        $persons = TableRegistry::getTableLocator()->get('Persons');

        return $persons->find()
            ->orderBy(['Persons.last' => 'ASC', 'Persons.first' => 'ASC'])
            ->all()
            ->toArray();
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
