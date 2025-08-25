<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Opponent Entity
 *
 * @property int $id
 * @property string|null $opponent_name
 * @property int|null $place_id
 * @property int|null $opponent_current Reference to current opponent (self-reference)
 * @property \App\Model\Entity\Opponent|null $current_opponent
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 */
class Opponent extends Entity
{
    // Custom methods or virtual fields can be added here
}
