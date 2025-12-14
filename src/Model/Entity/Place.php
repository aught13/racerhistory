<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Place Entity
 *
 * @property int $id
 * @property string $place_name
 * @property string $place_state
 * @property \DateTimeInterface|string|null $created_at
 * @property \DateTimeInterface|string|null $updated_at
 * @property string|null $place_city Virtual field for backwards compatibility
 */
class Place extends Entity
{
    /**
     * List of virtual/computed properties.
     *
     * @var array<string>
     */
    protected array $_virtual = ['place_city'];

    /**
     * Virtual field: place_city provides backwards compatibility.
     * The actual city data is stored in place_name, so this returns place_name.
     *
     * @return string|null
     */
    protected function _getPlaceCity(): ?string
    {
        return $this->place_name;
    }
}
