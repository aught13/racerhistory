<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TeamSeasonRosters Entity
 *
 * Represents a person's participation in a team season with roster details.
 *
 * @property int $id
 * @property int $team_season_id
 * @property int $person_id
 * @property string|null $roster_year
 * @property string|null $roster_number
 * @property string|null $roster_position
 * @property string|null $roster_height
 * @property string|null $roster_weight
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 *
 * @property \App\Model\Entity\TeamSeason $team_season
 * @property \App\Model\Entity\Person $person
 */
class TeamSeasonRosters extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it) and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'team_season_id' => true,
        'person_id' => true,
        'roster_year' => true,
        'roster_number' => true,
        'roster_position' => true,
        'roster_height' => true,
        'roster_weight' => true,
        'team_season' => true,
        'person' => true,
    ];
}
