<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StatBasketGameTeam Entity
 *
 * @property int $id
 * @property int $game_id
 * @property int $opp
 * @property string|null $ORB
 * @property string|null $DRB
 * @property string|null $RB
 * @property string|null $TRN
 * @property string|null $TF
 * @property string|null $PTS
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Game $game
 */
class StatBasketGameTeam extends Entity
{
    // Add custom methods or virtual fields if needed
}
