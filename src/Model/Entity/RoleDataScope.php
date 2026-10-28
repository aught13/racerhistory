<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $role_id
 * @property string $scope_type
 * @property string|null $sport_key
 * @property int|null $team_id
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\Team|null $team
 */
class RoleDataScope extends Entity
{
    /**
     * @var array<string,bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
