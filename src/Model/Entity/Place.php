<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Place Entity
 *
 * @property int $id
 * @property string $place_country ISO 3166 alpha-3 country code
 * @property string $place_city Locality (city, town, or village)
 * @property string $place_state Administrative subdivision (state, province, or region)
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 */
class Place extends Entity
{
}
