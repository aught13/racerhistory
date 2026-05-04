<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StatBasketGameTeam Entity
 *
 * @property int $id
 * @property int|null $game_id
 * @property bool $opp
 * @property string|null $ORB
 * @property string|null $DRB
 * @property string|null $RB
 * @property string|null $TRN
 * @property string|null $TF
 * @property string|null $PTS
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Game|null $game
 */
class StatBasketGameTeam extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'game_id' => true,
        'opp' => true,
        'ORB' => true,
        'DRB' => true,
        'RB' => true,
        'TRN' => true,
        'TF' => true,
        'PTS' => true,
        'created_at' => true,
        'updated_at' => true,
    ];
    // Add custom methods or virtual fields if needed
}
