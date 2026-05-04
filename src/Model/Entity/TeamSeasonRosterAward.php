<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $team_season_roster_id
 * @property string $award_type
 * @property string $award_category
 * @property string $award_name
 * @property \App\Model\Entity\TeamSeasonRosters $team_season_roster
 */
class TeamSeasonRosterAward extends Entity
{
    // Add custom methods or virtual fields if needed
}
