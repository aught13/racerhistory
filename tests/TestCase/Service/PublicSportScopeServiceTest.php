<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PublicSportScopeService;
use Cake\TestSuite\TestCase;

class PublicSportScopeServiceTest extends TestCase
{
    /**
     * @var array<int,string>
     */
    public array $fixtures = [
        'app.Sports',
        'app.Teams',
    ];

    private PublicSportScopeService $service;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PublicSportScopeService();
    }

    /**
     * @return void
     */
    public function testMissingScopeResolvesToAllSportsAndTeams(): void
    {
        $this->assertSame([
            'valid' => true,
            'error' => null,
            'sportKey' => null,
            'teamId' => null,
        ], $this->service->resolve([]));
    }

    /**
     * @return void
     */
    public function testSelectedTeamImpliesItsSport(): void
    {
        $this->assertSame([
            'valid' => true,
            'error' => null,
            'sportKey' => 'football',
            'teamId' => 3,
        ], $this->service->resolve(['team' => '3']));
    }

    /**
     * @return void
     */
    public function testRejectsTeamOutsideSelectedSport(): void
    {
        $scope = $this->service->resolve([
            'sport' => 'basketball',
            'team' => '3',
        ]);

        $this->assertFalse($scope['valid']);
        $this->assertSame('team_sport_mismatch', $scope['error']);
        $this->assertNull($scope['sportKey']);
        $this->assertNull($scope['teamId']);
    }

    /**
     * @return void
     */
    public function testRejectsUnknownTeamInsteadOfBroadeningScope(): void
    {
        $scope = $this->service->resolve(['team' => '999']);

        $this->assertFalse($scope['valid']);
        $this->assertSame('invalid_team', $scope['error']);
        $this->assertNull($scope['sportKey']);
        $this->assertNull($scope['teamId']);
    }

    /**
     * @return void
     */
    public function testListsOnlySportsWithTeams(): void
    {
        $this->assertSame([
            'basketball' => 'Basketball',
            'football' => 'Football',
        ], $this->service->getAvailableSportOptions());
    }

    /**
     * @return void
     */
    public function testTeamOptionsCarryTheirSportAndDisambiguateAllSports(): void
    {
        $teamOptions = $this->service->getTeamOptions();

        $this->assertSame([
            'label' => "Men's New York Giants (Football)",
            'sportKey' => 'football',
        ], $teamOptions[3]);
    }

    /**
     * @return void
     */
    public function testTeamOptionsCanBeRestrictedToOneSport(): void
    {
        $teamOptions = $this->service->getTeamOptions('football');

        $this->assertSame([3], array_keys($teamOptions));
        $this->assertSame("Men's New York Giants", $teamOptions[3]['label']);
    }
}
