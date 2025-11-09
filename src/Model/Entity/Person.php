<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Person Entity
 *
 * @property int $id
 * @property string|null $first
 * @property string|null $last
 * @property string|null $full
 * @property string $display
 * @property string|null $person_image
 * @property string|null $bio
 * @property mixed $birth
 * @property mixed $death
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property string $label
 * @property \App\Model\Entity\TeamSeasonRosters[] $team_season_rosters
 */
class Person extends Entity
{
    protected array $_virtual = ['label'];

    /**
     * Returns a display label for the person (public for PHPStan compliance).
     *
     * @return string
     */
    public function getLabel(): string
    {
        if (!empty($this->display)) {
            return $this->display;
        }
        $first = $this->first ?? '';
        $last = $this->last ?? '';
        $fullName = trim((string)$first . ' ' . (string)$last);
        if ($fullName) {
            return $fullName;
        }
        if (!empty($this->id)) {
            return 'Person #' . $this->id;
        }

        return 'Unknown Person';
    }
}
