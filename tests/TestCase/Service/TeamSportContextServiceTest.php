<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TeamSportContextService;
use Cake\TestSuite\TestCase;

class TeamSportContextServiceTest extends TestCase
{
    private TeamSportContextService $service;

    /**
     * Test setup: instantiate TeamSportContextService.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new TeamSportContextService();
    }

    /**
     * Test teardown: unset service.
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Resolve sport key directly from team object with sport_key present.
     *
     * @return void
     */
    public function testResolveSportKeyFromTeamWithSportKey(): void
    {
        $team = (object)['sport_key' => 'basketball'];
        $this->assertSame('basketball', $this->service->resolveSportKeyFromTeam($team));
    }

    /**
     * Resolve sport key from legacy sport object on team.
     *
     * @return void
     */
    public function testResolveSportKeyFromTeamWithLegacySportObject(): void
    {
        $team = (object)['sport' => (object)['sport_name' => 'Basketball']];
        $this->assertSame('basketball', $this->service->resolveSportKeyFromTeam($team));
    }

    /**
     * Build filter conditions for sport key with fallback behaviours.
     *
     * @return void
     */
    public function testBuildSportFilterConditionsFallbacks(): void
    {
        // When invalid sport key is provided, empty conditions
        $conds = $this->service->buildSportFilterConditions('', 'Teams');
        $this->assertSame([], $conds);

        // Valid known sport creates OR conditions or key equality
        $conds = $this->service->buildSportFilterConditions('basketball', 'Teams');
        $this->assertIsArray($conds);
        $this->assertNotEmpty($conds);
    }
}
