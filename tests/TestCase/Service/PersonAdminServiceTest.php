<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Person;
use App\Model\Table\PersonsTable;
use App\Service\PersonAdminService;
use App\Service\StatsService;
use Cake\TestSuite\TestCase;

class PersonAdminServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.StatBasketSeasonPerson',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.StatBasketGamePerson',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

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

    /**
     * Verify admin career data includes basketball advanced totals.
     *
     * @return void
     */
    public function testGetViewDataIncludesBasketballCareerAdvancedStats(): void
    {
        $service = new PersonAdminService($this->fetchTable('Persons'), new StatsService());
        $viewData = $service->getViewData('1');
        $careerData = $viewData['careerStatsBySport'][1];

        $this->assertIsArray($careerData['advancedStats']);
        $this->assertEqualsWithDelta(0.5917159763, $careerData['advancedStats']['TS%'], 0.0001);
        $this->assertGreaterThan(0, $careerData['advancedStats']['PProd']);
    }
}
