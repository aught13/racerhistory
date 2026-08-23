<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $role_id
 * @property string $model_name
 * @property bool $can_create
 * @property string $can_read
 * @property string $can_update
 * @property string $can_delete
 * @property array<string, mixed>|null $custom_rules
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Role $role
 */
class Permission extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
