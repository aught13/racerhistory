<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Image Entity fields
 *
 * @property int $id
 * @property int|null $user_id
 * @property \App\Model\Entity\User|null $user
 * @property string|null $photo_credit
 * @property string $filename
 * @property string|null $original_name
 * @property string $mime
 * @property string|null $ext
 * @property int $byte_size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $variants
 * @property string $hash
 * @property string $status
 * @property string|null $storage_subdir
 * @property string $storage_path
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\ImageTag[] $image_tags
 * @property \Cake\ORM\Entity $_joinData
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
        'photo_credit' => true,
        'user_id' => true,
    ];
}
