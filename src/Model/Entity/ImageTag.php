<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ImageTag Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \DateTimeInterface $created
 * @property \DateTimeInterface $modified
 *
 * @property \App\Model\Entity\Image[] $images
 */
class ImageTag extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
        'created' => true,
        'modified' => true,
        'images' => true,
    ];
}
