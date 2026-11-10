<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Season;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use Cake\TestSuite\TestCase;

class TeamSeasonEntityTest extends TestCase
{
    /**
     * Team season labels use the canonical team display name.
     */
    public function testGetDisplayNameUsesTeamDisplayName(): void
    {
        $team = new Team([
            'team_name' => 'Basketball',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_FEMALE,
        ]);
        $season = new Season(['start' => 2023, 'end' => 2024]);
        $teamSeason = new TeamSeason();
        $teamSeason->team = $team;
        $teamSeason->season = $season;

        $this->assertSame(
            $team->getDisplayName() . ' (' . $season->getDisplayName() . ')',
            $teamSeason->getDisplayName(),
        );
    }

    /**
     * Team season labels retain their fallback when associations are absent.
     */
    public function testGetDisplayNameUsesUnknownAssociationFallbacks(): void
    {
        $this->assertSame('Unknown Team (Unknown Season)', (new TeamSeason())->getDisplayName());
    }
}
