<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int|null $game_id
 * @property int|null $opponent_id
 * @property string|null $period
 * @property string|null $GP
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
 * @property string|null $TRN
 * @property string|null $PF
 * @property string|null $TF
 * @property string|null $PTS
 * @property string|null $PNT
 * @property string|null $OTO
 * @property string|null $SND
 * @property string|null $FB
 * @property string|null $BN
 * @property string|null $TIED
 * @property string|null $LC
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 * @property \App\Model\Entity\Game|null $game
 * @property \App\Model\Entity\Opponent|null $opponent
 */
class StatBasketGameBox extends Entity
{
    // Add custom methods or virtual fields if needed
}
