<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Image Entity fields
 *
 * @property int $id
 * @property string $filename
 * @property string|null $original_name
 * @property string $mime
 * @property string|null $ext
 * @property int $byte_size
 * @property int|null $width
 * @property int|null $height
 * @property array|string|null $variants
 * @property string $hash
 * @property string $status
 * @property string|null $storage_subdir
 * @property string|null $storage_path
 * @property mixed $created
 * @property mixed $modified
 *
 * @property \App\Model\Entity\ImageTag[] $image_tags
 */
class Image extends Entity
{
    protected array $_accessible = [
        'filename' => true,
    'storage_subdir' => true,
    'storage_path' => true,
        'original_name' => true,
        'mime' => true,
        'ext' => true,
        'byte_size' => true,
        'width' => true,
        'height' => true,
        'variants' => true,
        'hash' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'usages' => true,
    ];
}
