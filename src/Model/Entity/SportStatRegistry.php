<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SportStatRegistry Entity
 *
 * @property int $id
 * @property int $sport_id
 * @property string $context
 * @property string $entity_type
 * @property string $table_name
 * @property string $display_name
 * @property string|null $field_mapping
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property array $mapped_fields Virtual property for decoded field mapping
 *
 * @property \App\Model\Entity\Sport $sport
 */
class SportStatRegistry extends Entity
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
        'sport_id' => true,
        'context' => true,
        'entity_type' => true,
        'table_name' => true,
        'display_name' => true,
        'field_mapping' => true,
        'created' => false,
        'modified' => false,
        'sport' => false,
    ];

    /**
     * Get decoded field mapping
     *
     * @return array
     */
    protected function _getMappedFields(): array
    {
        if (empty($this->field_mapping)) {
            return [];
        }

        $decoded = json_decode($this->field_mapping, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded;
    }

    /**
     * Set field mapping from array
     *
     * @param array $mapping Field mapping array
     * @return void
     */
    protected function _setMappedFields(array $mapping): void
    {
        $this->field_mapping = json_encode($mapping);
    }

    /**
     * Get field label for a specific field
     *
     * @param string $field Field code
     * @return string Label or original field if not found
     */
    public function getFieldLabel(string $field): string
    {
        $mapping = $this->mapped_fields;

        return $mapping[$field] ?? $field;
    }

    /**
     * Check if this registry entry is for a team-level stat table
     *
     * @return bool
     */
    public function isTeamStat(): bool
    {
        return $this->entity_type === 'team';
    }

    /**
     * Check if this registry entry is for a player-level stat table
     *
     * @return bool
     */
    public function isPlayerStat(): bool
    {
        return $this->entity_type === 'player';
    }

    /**
     * Check if this registry entry is for an opponent-level stat table
     *
     * @return bool
     */
    public function isOpponentStat(): bool
    {
        return $this->entity_type === 'opponent';
    }

    /**
     * Check if this registry entry is for a game-level stat table
     *
     * @return bool
     */
    public function isGameStat(): bool
    {
        return $this->context === 'game';
    }

    /**
     * Check if this registry entry is for a season-level stat table
     *
     * @return bool
     */
    public function isSeasonStat(): bool
    {
        return $this->context === 'season';
    }
}
