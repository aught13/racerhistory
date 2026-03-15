<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * GameType Entity
 *
 * @property int $id
 * @property string|null $game_type_name
 * @property bool|null $post
 * @property bool|null $conf
 * @property string|null $abr
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\Game[] $games
 */
class GameType extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'game_type_name' => true,
        'post' => true,
        'conf' => true,
        'abr' => true,
        'created' => false,
        'modified' => false,
        'games' => false,
    ];
}
