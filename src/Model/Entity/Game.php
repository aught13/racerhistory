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
 * @property string|null $game_date
 * @property string|null $game_time
 * @property string|null $game_duration
 * @property int|null $hrn
 * @property int|null $periods
 * @property int|null $ot
 * @property int|null $pts_mur
 * @property int|null $pts_opp
 * @property int|null $mur_rk
 * @property int|null $opp_rk
 * @property int|null $w
 * @property int|null $l
 * @property string|null $wl
 * @property int|null $attendance
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 * @property \App\Model\Entity\TeamSeason|null $team_season
 * @property \App\Model\Entity\Opponent|null $opponent
 * @property \App\Model\Entity\GameType|null $game_type
 * @property \App\Model\Entity\Place|null $place
 * @property \App\Model\Entity\Site|null $site
 */
class Game extends Entity
{
    // Add custom methods or virtual fields if needed
}
