<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Sport Entity
 *
 * Represents a sport category in the application's historical sports information and statistics.
 * Sports are the foundation categories that teams compete in.
 *
 * @property int $id Unique identifier
 * @property string $sport_name Name of the sport (unique, max 162 chars)
 * @property \Cake\I18n\DateTime|null $created_at Creation timestamp
 * @property \Cake\I18n\DateTime|null $updated_at Last modification timestamp
 *
 * @property \App\Model\Entity\Team[] $teams Teams that compete in this sport
 *
 * Legacy note:
 * Sport configuration/stat metadata no longer belongs on the Sport entity.
 * Runtime config is now sourced via SportConfigService + SiteOptions.
 */
class Sport extends Entity
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
        'sport_name' => true,
        'created_at' => true,
        'updated_at' => true,
    ];

    /**
     * Get display name for this sport
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->sport_name ?? 'Unknown Sport';
    }
}
