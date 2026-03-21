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
 * @property \App\Model\Entity\SportConfig[] $sport_configs Sport-specific configurations
 * @property \App\Model\Entity\SportStatRegistry[] $sport_stat_registry Stat table registrations
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

    /**
     * Get supported period counts for this sport
     *
     * @return array<int>
     */
    public function getSupportedPeriods(): array
    {
        foreach ((array)($this->sport_configs ?? []) as $config) {
            if (!$config instanceof \App\Model\Entity\SportConfig) {
                continue;
            }
            if ($config->config_key === 'supports_periods') {
                $decoded = json_decode($config->config_value, true);
                if (is_array($decoded)) {
                    return array_map('intval', $decoded);
                }
                break;
            }
        }

        // Default to standard basketball periods
        return [2, 4];
    }

    /**
     * Get default period count for this sport
     *
     * @return int
     */
    public function getDefaultPeriods(): int
    {
        foreach ((array)($this->sport_configs ?? []) as $config) {
            if (!$config instanceof \App\Model\Entity\SportConfig) {
                continue;
            }
            if ($config->config_key === 'default_periods') {
                return (int)$config->config_value;
            }
        }

        // Default value
        return 4;
    }

    /**
     * Get name for a specific period count
     *
     * @param int $periodCount Number of periods
     * @return string
     */
    public function getPeriodName(int $periodCount): string
    {
        foreach ((array)($this->sport_configs ?? []) as $config) {
            if (!$config instanceof \App\Model\Entity\SportConfig) {
                continue;
            }
            if ($config->config_key === "period_name_{$periodCount}") {
                return $config->config_value;
            }
        }

        // Default names
        return match ($periodCount) {
            2 => 'Half',
            4 => 'Quarter',
            9 => 'Inning',
            default => 'Period',
        };
    }

    /**
     * Get officials array for this sport
     *
     * @return array<string>
     */
    public function getOfficials(): array
    {
        foreach ((array)($this->sport_configs ?? []) as $config) {
            if (!$config instanceof \App\Model\Entity\SportConfig) {
                continue;
            }
            if ($config->config_key === 'officials') {
                $decoded = json_decode($config->config_value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                break;
            }
        }

        // Default officials
        return ['Referee', 'Umpire'];
    }

    /**
     * Get scoring type for this sport
     *
     * @return string
     */
    public function getScoringType(): string
    {
        foreach ((array)($this->sport_configs ?? []) as $config) {
            if (!$config instanceof \App\Model\Entity\SportConfig) {
                continue;
            }
            if ($config->config_key === 'scoring_type') {
                return $config->config_value;
            }
        }

        // Default to cumulative scoring (most sports)
        return 'cumulative';
    }

    /**
     * Get stat tables for this sport
     *
     * @param string $context Optional context filter (game, season)
     * @param string $entityType Optional entity type filter (team, player, opponent, box)
     * @return array<\App\Model\Entity\SportStatRegistry>
     */
    public function getStatTables(?string $context = null, ?string $entityType = null): array
    {
        if (empty($this->sport_stat_registry)) {
            return [];
        }

        $registry = (array)$this->sport_stat_registry;

        if ($context !== null) {
            $registry = array_filter($registry, function (\App\Model\Entity\SportStatRegistry $item) use ($context) {
                return $item->context === $context;
            });
        }

        if ($entityType !== null) {
            $registry = array_filter($registry, function (\App\Model\Entity\SportStatRegistry $item) use ($entityType) {
                return $item->entity_type === $entityType;
            });
        }

        return array_values($registry);
    }

    /**
     * Get specific stat table configuration
     *
     * @param string $context Context (game, season)
     * @param string $entityType Entity type (team, player, opponent, box)
     * @return \App\Model\Entity\SportStatRegistry|null
     */
    public function getStatTable(string $context, string $entityType): ?SportStatRegistry
    {
        if (empty($this->sport_stat_registry)) {
            return null;
        }

        foreach ((array)$this->sport_stat_registry as $registry) {
            if (!($registry instanceof \App\Model\Entity\SportStatRegistry)) {
                continue;
            }
            if ($registry->context === $context && $registry->entity_type === $entityType) {
                return $registry;
            }
        }

        return null;
    }
}
