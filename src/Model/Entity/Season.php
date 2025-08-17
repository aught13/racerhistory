<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Season Entity
 *
 * Represents a season period in the application's historical sports information and statistics.
 * Seasons define time periods during which teams compete.
 *
 * @property int $id Unique identifier
 * @property string $start Starting year of the season
 * @property string $end Ending year of the season
 * @property \Cake\I18n\DateTime|null $created_at Creation timestamp
 * @property \Cake\I18n\DateTime|null $updated_at Last modification timestamp
 *
 * @property \App\Model\Entity\TeamSeason[] $team_seasons Team seasons in this season
 */
class Season extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'start' => true,
        'end' => true,
        'created_at' => true,
        'updated_at' => true,
    ];

    /**
     * Get display name for this season
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return ($this->start ?? 'Unknown') . '-' . ($this->end ?? 'Unknown');
    }
}
