<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ImageUsage extends Entity
{
    protected array $_accessible = [
        'image_id' => true,
        'model' => true,
        'foreign_key' => true,
        'context' => true,
        'field' => true,
        'created' => true,
        'modified' => true,
        'image' => true,
    ];
}
