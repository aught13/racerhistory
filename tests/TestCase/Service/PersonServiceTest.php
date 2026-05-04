<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PersonService;
use Cake\TestSuite\TestCase;

class PersonServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Persons',
    ];

    private PersonService $service;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PersonService();
    }

    /**
     * Tests get person by id.
     */
    public function testGetPersonById(): void
    {
        $person = $this->service->getPersonById(1);
        $this->assertNotNull($person);
        $this->assertSame(1, $person->id);
    }

    /**
     * Tests get person by id returns null for invalid id.
     */
    public function testGetPersonByIdReturnsNullForInvalidId(): void
    {
        $person = $this->service->getPersonById(99999);
        $this->assertNull($person);
    }

    /**
     * Tests get display label.
     */
    public function testGetDisplayLabel(): void
    {
        $label = $this->service->getDisplayLabel(1);
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    /**
     * Tests get display label fallback for invalid id.
     */
    public function testGetDisplayLabelFallbackForInvalidId(): void
    {
        $label = $this->service->getDisplayLabel(99999);
        $this->assertSame('Person #99999', $label);
    }

    /**
     * Tests search persons.
     */
    public function testSearchPersons(): void
    {
        $results = $this->service->searchPersons('Doe', 10);
        $this->assertIsArray($results);
        if (!empty($results)) {
            $first = $results[0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('label', $first);
            $this->assertArrayHasKey('disambiguate', $first);
        }
    }

    /**
     * Tests search persons returns empty for empty query.
     */
    public function testSearchPersonsReturnsEmptyForEmptyQuery(): void
    {
        $results = $this->service->searchPersons('');
        $this->assertSame([], $results);
    }

    /**
     * Tests search persons respects limit.
     */
    public function testSearchPersonsRespectsLimit(): void
    {
        $results = $this->service->searchPersons('a', 5);
        $this->assertLessThanOrEqual(5, count($results));
    }

    /**
     * Tests get all persons.
     */
    public function testGetAllPersons(): void
    {
        $persons = $this->service->getAllPersons();
        $this->assertIsArray($persons);
        $this->assertGreaterThan(0, count($persons));
    }

    /**
     * Tests create person.
     */
    public function testCreatePerson(): void
    {
        $data = [
            'first' => 'Test',
            'last' => 'Person',
            'display' => 'Test Person',
        ];
        $person = $this->service->createPerson($data);
        $this->assertNotFalse($person);
        $this->assertSame('Test', $person->first);
        $this->assertSame('Person', $person->last);
    }

    /**
     * Tests update person.
     */
    public function testUpdatePerson(): void
    {
        $person = $this->service->updatePerson(1, ['first' => 'Updated']);
        $this->assertNotFalse($person);
        $this->assertSame('Updated', $person->first);
    }

    /**
     * Tests delete person.
     */
    public function testDeletePerson(): void
    {
        $data = [
            'first' => 'Delete',
            'last' => 'Me',
            'display' => 'Delete Me',
        ];
        $person = $this->service->createPerson($data);
        $this->assertNotFalse($person);

        $result = $this->service->deletePerson($person->id);
        $this->assertTrue($result);

        $deleted = $this->service->getPersonById($person->id);
        $this->assertNull($deleted);
    }

    /**
     * Tests get persons for select.
     */
    public function testGetPersonsForSelect(): void
    {
        $results = $this->service->getPersonsForSelect();
        $this->assertIsArray($results);
        if (!empty($results)) {
            $first = $results[0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('label', $first);
        }
    }

    /**
     * Tests get persons list.
     */
    public function testGetPersonsList(): void
    {
        $list = $this->service->getPersonsList(200);
        $this->assertIsArray($list);
        $this->assertArrayHasKey(1, $list);
        $this->assertSame('John Doe', $list[1]);
    }
}
