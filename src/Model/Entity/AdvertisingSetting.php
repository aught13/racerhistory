<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $global_script
 * @property string $publisher_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class AdvertisingSetting extends Entity
{
    /**
     * Mass assignment rules.
     *
     * @var array<string,bool>
     */
    protected array $_accessible = [
        'id' => true,
        'global_script' => true,
        'publisher_id' => true,
        'created' => true,
        'modified' => true,
    ];
}
