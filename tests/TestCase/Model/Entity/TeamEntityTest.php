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
     * Test getDisplayName composes the default team name.
     */
    public function testGetDisplayName(): void
    {
        $team = new Team([
            'team_name' => 'Basketball',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);
        $this->assertSame("Racers Men's Basketball", $team->getDisplayName());
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
     * Test getDisplayName does not repeat a gender already present in a name.
     */
    public function testGetDisplayNameDoesNotRepeatGenderInName(): void
    {
        $team = new Team([
            'team_name' => 'Women’s Basketball',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_FEMALE,
        ]);

        $this->assertSame('Racers Women’s Basketball', $team->getDisplayName());
    }

    /**
     * Test gender already stated in a name is not contradicted by the classification.
     */
    public function testGetDisplayNameDoesNotAddConflictingGenderToName(): void
    {
        $team = new Team([
            'team_name' => "Women's Basketball",
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);

        $this->assertSame("Racers Women's Basketball", $team->getDisplayName());
    }

    /**
     * Test getDisplayName omits a gender prefix when there is no base team name.
     */
    public function testGetDisplayNameOmitsGenderWithoutTeamName(): void
    {
        $team = new Team([
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);

        $this->assertSame('Racers', $team->getDisplayName());
    }

    /**
     * Test an unknown gender value does not produce a made-up display prefix.
     */
    public function testGetDisplayNameOmitsUnknownGender(): void
    {
        $team = new Team([
            'team_name' => 'Basketball',
            'team_nickname' => 'Racers',
            'gender' => 'X',
        ]);

        $this->assertSame('Racers Basketball', $team->getDisplayName());
    }

    /**
     * Test getFullTeamDisplayName joins the full set of name components.
     */
    public function testGetFullTeamDisplayName(): void
    {
        $team = new Team([
            'team_description' => 'Murray State',
            'team_nickname' => 'Racers',
            'team_name' => 'Basketball',
            'gender' => Team::GENDER_FEMALE,
        ]);

        $this->assertSame("Murray State Racers Women's Basketball", $team->getFullTeamDisplayName());
    }

    /**
     * Test getFullTeamDisplayName omits gender when the description already states it.
     */
    public function testGetFullTeamDisplayNameDoesNotRepeatGenderInDescription(): void
    {
        $team = new Team([
            'team_description' => "Murray State Women's",
            'team_nickname' => 'Racers',
            'team_name' => 'Basketball',
            'gender' => Team::GENDER_FEMALE,
        ]);

        $this->assertSame("Murray State Women's Racers Basketball", $team->getFullTeamDisplayName());
    }

    /**
     * Test getTeamDisplayName composes the short team label.
     */
    public function testGetTeamDisplayName(): void
    {
        $team = new Team([
            'team_name' => 'Basketball',
            'gender' => Team::GENDER_FEMALE,
        ]);

        $this->assertSame("Women's Basketball", $team->getTeamDisplayName());
    }

    /**
     * Test co-ed gender is omitted from each display variant.
     */
    public function testDisplayVariantsOmitCoedGender(): void
    {
        $team = new Team([
            'team_description' => 'Murray State',
            'team_nickname' => 'Racers',
            'team_name' => 'Bowling',
            'gender' => Team::GENDER_COED,
        ]);

        $this->assertSame('Racers Bowling', $team->getDisplayName());
        $this->assertSame('Murray State Racers Bowling', $team->getFullTeamDisplayName());
        $this->assertSame('Bowling', $team->getTeamDisplayName());
    }

    /**
     * Test a repeated exact component is included once.
     */
    public function testDisplayNameOmitsRepeatedExactComponents(): void
    {
        $team = new Team([
            'team_name' => 'Racers',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);

        $this->assertSame("Men's Racers", $team->getDisplayName());
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
