<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $place_id
 * @property string $site_name
 * @property string|null $capacity
 * @property string|null $site_image
 * @property string|null $site_info
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 * @property \App\Model\Entity\Place $place
 */
class Site extends Entity
{
    // Add custom methods or virtual fields if needed
}
