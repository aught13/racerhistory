<?php
declare(strict_types=1);

namespace App\Service;

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
    public function getPersonById(int $personId): ?\App\Model\Entity\Person
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
    public function createPerson(array $data): \App\Model\Entity\Person|false
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
    public function updatePerson(int $personId, array $data): \App\Model\Entity\Person|false
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
}
