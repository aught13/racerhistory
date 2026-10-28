<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Person;
use App\Model\Table\PersonsTable;
use App\Service\PersonAdminService;
use Cake\TestSuite\TestCase;

class PersonAdminServiceTest extends TestCase
{
    protected array $fixtures = ['app.Persons'];

    /**
     * Verify the add workflow receives an unsaved person entity.
     *
     * @return void
     */
    public function testNewEntityReturnsAnUnsavedPerson(): void
    {
        $personsTable = $this->getTableLocator()->get('Persons');
        $this->assertInstanceOf(PersonsTable::class, $personsTable);
        $service = new PersonAdminService($personsTable);

        $person = $service->newEntity();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertNull($person->id);
        $this->assertSame([], $person->getErrors());
    }
}
