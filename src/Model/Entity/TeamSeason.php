<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TeamSeason Entity
 *
 * Represents a team's participation in a specific season with detailed competition information.
 * Links teams to seasons and contains season-specific data.
 *
 * @property int $id Unique identifier
 * @property int $team_id Foreign key to teams table
 * @property int $season_id Foreign key to seasons table
 * @property int $semester Semester number
 * @property string|null $league League name
 * @property string|null $league_abbr League abbreviation
 * @property string|null $league_finish League finishing position
 * @property string|null $league_torunament_finish League tournament finish
 * @property string|null $last_post_game Last post game information
 * @property string|null $team_season_notes Season notes
 * @property string|null $team_season_image Season image filename
 * @property string|null $team_season_preview Season preview text
 * @property string|null $team_season_recap Season recap text
 * @property \Cake\I18n\DateTime|null $created_at Creation timestamp
 * @property \Cake\I18n\DateTime|null $updated_at Last modification timestamp
 *
 * @property \App\Model\Entity\Team $team Team entity
 * @property \App\Model\Entity\Season $season Season entity
 */
class TeamSeason extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'team_id' => true,
        'season_id' => true,
        'semester' => true,
        'league' => true,
        'league_abbr' => true,
        'league_finish' => true,
        'league_torunament_finish' => true,
        'last_post_game' => true,
        'team_season_notes' => true,
        'team_season_image' => true,
        'team_season_preview' => true,
        'team_season_recap' => true,
        'created_at' => true,
        'updated_at' => true,
    ];

    /**
     * Get display name for this team season
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        $teamName = $this->team->team_name ?? 'Unknown Team';
        $seasonName = isset($this->season) ? $this->season->getDisplayName() : 'Unknown Season';

        return $teamName . ' (' . $seasonName . ')';
    }
}
