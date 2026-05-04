<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $team_season_id
 * @property int|null $opponent_id
 * @property int|null $game_type_id
 * @property int|null $place_id
 * @property int|null $site_id
 * @property \Cake\I18n\Date|null $game_date
 * @property string|null $game_time
 * @property string|null $game_duration
 * @property int $hrn
 * @property string|null $periods
 * @property string|null $ot
 * @property string|null $pts_mur
 * @property string|null $pts_opp
 * @property string|null $mur_rk
 * @property string|null $opp_rk
 * @property string|null $w
 * @property string|null $l
 * @property string|null $wl
 * @property string|null $attendance
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 * @property \App\Model\Entity\TeamSeason $team_season
 * @property \App\Model\Entity\Opponent|null $opponent
 * @property \App\Model\Entity\GameType|null $game_type
 * @property \App\Model\Entity\Place|null $place
 * @property \App\Model\Entity\Site|null $site
 * @property bool $post
 * @property string $game_status
 * @property string|null $weather_conditions
 * @property string|null $surface_type
 * @property string|null $game_preview
 * @property string|null $game_recap
 * @property string|null $game_notes
 */
class Game extends Entity
{
    // Add custom methods or virtual fields if needed
}
