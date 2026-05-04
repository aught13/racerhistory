<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SportConfig Entity
 *
 * @property int $id
 * @property int $sport_id
 * @property string $config_key
 * @property string|null $config_value
 * @property string|null $description
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property mixed $decoded_value Virtual property for decoded JSON values
 *
 * @property \App\Model\Entity\Sport $sport
 */
class SportConfig extends Entity
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
        'config_key' => true,
        'config_value' => true,
        'description' => true,
        'created' => true,
        'modified' => true,
        'sport' => true,
    ];

    /**
     * Get decoded config value (handles JSON)
     *
     * @return mixed Decoded value
     * @see \App\Model\Entity\SportConfig::$decoded_value
     */
    protected function _getDecodedValue(): mixed
    {
        if (empty($this->config_value)) {
            return null;
        }

        $decoded = json_decode($this->config_value, true);

        return $decoded ?? $this->config_value;
    }

    /**
     * Check if config value is JSON
     *
     * @return bool True if JSON
     */
    public function isJsonValue(): bool
    {
        if (empty($this->config_value)) {
            return false;
        }

        json_decode($this->config_value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get user-friendly display value
     *
     * @return string Display value
     */
    public function getDisplayValue(): string
    {
        if ($this->isJsonValue()) {
            $decoded = $this->decoded_value;
            if (is_array($decoded)) {
                return implode(', ', $decoded);
            }
        }

        return (string)$this->config_value;
    }
}
