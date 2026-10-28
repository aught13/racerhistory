<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Person;
use App\Service\PeopleDirectoryService;
use App\Service\PersonService;
use App\Service\PublicSportScopeService;
use App\Service\StatsService;
use App\Service\TeamSportContextService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class PeopleDirectoryServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.StatBasketGamePerson',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

    private PeopleDirectoryService $service;

    /**
     * Initialize the directory service with fixture-backed tables.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PeopleDirectoryService(
            TableRegistry::getTableLocator()->get('Persons'),
            TableRegistry::getTableLocator()->get('TeamSeasonRosters'),
            new PersonService(),
            new StatsService(),
            new TeamSportContextService(),
            new PublicSportScopeService(),
        );
    }

    /**
     * Verify people counts respect the selected roster scope.
     *
     * @return void
     */
    public function testCountPeopleUsesRosterScope(): void
    {
        $allCount = $this->service->countPeople(null, null);
        $basketballCount = $this->service->countPeople('basketball', 1);

        $this->assertGreaterThan(0, $allCount);
        $this->assertSame(1, $basketballCount);
    }

    /**
     * Verify profile data includes roster and career-stat groups.
     *
     * @return void
     */
    public function testProfileViewDataIncludesRosterAndCareerGroups(): void
    {
        $viewData = $this->service->profileViewData(1, [
            'valid' => true,
            'error' => null,
            'sportKey' => null,
            'teamId' => null,
        ], false);

        $this->assertNotNull($viewData);
        $this->assertInstanceOf(Person::class, $viewData['person']);
        $this->assertNotEmpty($viewData['rosterEntries']);
        $this->assertIsArray($viewData['rostersBySport']);
        $this->assertIsArray($viewData['careerStatsBySport']);
        $this->assertIsArray($viewData['gameLogGroups']);
    }

    /**
     * Verify game-log view data enforces roster scope and missing IDs.
     *
     * @return void
     */
    public function testGameLogViewDataRejectsMissingRosterAndReturnsExistingPerson(): void
    {
        $person = $this->service->getPersonById(1);
        $this->assertInstanceOf(Person::class, $person);

        $scope = [
            'valid' => true,
            'error' => null,
            'sportKey' => null,
            'teamId' => null,
        ];
        $viewData = $this->service->gameLogViewData($person, 1, $scope);
        $this->assertNotNull($viewData);
        $this->assertSame('person-game-log-frame-1-1', $viewData['frameId']);

        $scope['teamId'] = 9999;
        $this->assertNull($this->service->gameLogViewData($person, 1, $scope));
        $this->assertNull($this->service->getPersonById(9999));
    }
}
