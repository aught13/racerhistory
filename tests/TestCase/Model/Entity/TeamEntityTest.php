<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Sport;
use App\Model\Entity\Team;
use Cake\TestSuite\TestCase;

/**
 * Team Entity Test Case
 */
class TeamEntityTest extends TestCase
{
    /**
     * Test getDisplayName returns team name
     */
    public function testGetDisplayName(): void
    {
        $team = new Team(['team_name' => 'Lakers']);
        $this->assertSame('Lakers', $team->getDisplayName());
    }

    /**
     * Test getDisplayName returns default
     */
    public function testGetDisplayNameDefault(): void
    {
        $team = new Team();
        $this->assertSame('Unknown Team', $team->getDisplayName());
    }

    /**
     * Test getGenderLabel for all genders
     */
    public function testGetGenderLabel(): void
    {
        $maleTeam = new Team(['gender' => Team::GENDER_MALE]);
        $this->assertSame('Male', $maleTeam->getGenderLabel());

        $femaleTeam = new Team(['gender' => Team::GENDER_FEMALE]);
        $this->assertSame('Female', $femaleTeam->getGenderLabel());

        $coedTeam = new Team(['gender' => Team::GENDER_COED]);
        $this->assertSame('Co-ed', $coedTeam->getGenderLabel());
    }

    /**
     * Test getGenderLabel returns unknown for invalid
     */
    public function testGetGenderLabelUnknown(): void
    {
        $team = new Team(['gender' => 'X']);
        $this->assertSame('Unknown', $team->getGenderLabel());
    }

    /**
     * Test getFullDisplayName with sport
     */
    public function testGetFullDisplayName(): void
    {
        $sport = new Sport(['sport_name' => 'Basketball']);
        $team = new Team(['team_name' => 'Lakers', 'sport' => $sport]);
        $this->assertSame('Lakers (Basketball)', $team->getFullDisplayName());
    }

    /**
     * Test getFullDisplayName without sport
     */
    public function testGetFullDisplayNameWithoutSport(): void
    {
        $team = new Team(['team_name' => 'Lakers']);
        $this->assertSame('Lakers (Unknown Sport)', $team->getFullDisplayName());
    }

    /**
     * Test getCompactName returns abbreviation
     */
    public function testGetCompactName(): void
    {
        $team = new Team(['abbr' => 'LAL']);
        $this->assertSame('LAL', $team->getCompactName());
    }

    /**
     * Test getCompactName returns default
     */
    public function testGetCompactNameDefault(): void
    {
        $team = new Team();
        $this->assertSame('UNK', $team->getCompactName());
    }

    /**
     * Test hasDescription returns true when description exists
     */
    public function testHasDescriptionTrue(): void
    {
        $team = new Team(['team_description' => 'Los Angeles Lakers']);
        $this->assertTrue($team->hasDescription());
    }

    /**
     * Test hasDescription returns false when empty
     */
    public function testHasDescriptionFalse(): void
    {
        $team = new Team();
        $this->assertFalse($team->hasDescription());
    }

    /**
     * Test getGenderOptions returns all options
     */
    public function testGetGenderOptions(): void
    {
        $options = Team::getGenderOptions();
        $this->assertArrayHasKey(Team::GENDER_MALE, $options);
        $this->assertArrayHasKey(Team::GENDER_FEMALE, $options);
        $this->assertArrayHasKey(Team::GENDER_COED, $options);
        $this->assertSame('Male', $options[Team::GENDER_MALE]);
        $this->assertSame('Female', $options[Team::GENDER_FEMALE]);
        $this->assertSame('Co-ed', $options[Team::GENDER_COED]);
    }
}
