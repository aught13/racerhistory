<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $option_key
 * @property string|null $value
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class SiteOption extends Entity
{
    // protected $_accessible = [
    //     'id' => false,
    //     '*' => true,
    // ];
    // Optionally add property annotations for option_key
}
