<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StatBasketGameOpponent Entity
 *
 * @property int $id
 * @property int $game_id
 * @property string $period
 * @property string $name
 * @property string|null $jersey
 * @property string|null $position
 * @property string|null $GP
 * @property string|null $GS
 * @property string|null $MIN
 * @property string|null $FGM
 * @property string|null $FGA
 * @property string|null $TPM
 * @property string|null $TPA
 * @property string|null $FTM
 * @property string|null $FTA
 * @property string|null $ORB
 * @property string|null $DRB
 * @property string|null $RB
 * @property string|null $AST
 * @property string|null $STL
 * @property string|null $BS
 * @property string|null $BD
 * @property string|null $TRN
 * @property string|null $PF
 * @property string|null $TF
 * @property string|null $FD
 * @property string|null $PTS
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Game $game
 */
class StatBasketGameOpponent extends Entity
{
    // Add custom methods or virtual fields if needed
}
