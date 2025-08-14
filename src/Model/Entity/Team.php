<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * @property int $id
 * @property int $sport_id
 * @property string $team_name
 * @property string|null $team_description
 * @property string $abbr
 * @property string $gender
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 *
 * @property \App\Model\Entity\Sport $sport
 */
class Team extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'sport_id' => true,
        'team_name' => true,
        'team_description' => true,
        'abbr' => true,
        'gender' => true,
        'created_at' => true,
        'updated_at' => true,
        'sport' => true,
    ];
}
