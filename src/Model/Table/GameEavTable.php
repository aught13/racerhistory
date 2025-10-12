<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

class GameEavTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_eav');
        $this->setPrimaryKey('id');
        $this->setDisplayField('key');
    }

    /**
     * Get all attributes for a game as key => value pairs.
     *
     * @param int $gameId Game id.
     * @return array<string, mixed> Key/value attribute list.
     */
    public function getAttributesForGame(int $gameId): array
    {
        $rows = $this->find()
            ->select(['key', 'value'])
            ->where(['game_id' => $gameId])
            ->all();
        $attributes = [];
        foreach ($rows as $row) {
            $attributes[$row->key] = $row->value;
        }

        return $attributes;
    }

    /**
     * Add or update an attribute for a game.
     *
     * @param int $gameId Game id.
     * @param string $key Attribute key.
     * @param scalar|null $value Attribute value.
     * @return \Cake\Datasource\EntityInterface|false Saved entity or false on failure.
     */
    public function setAttribute(
        int $gameId,
        string $key,
        int|float|string|bool|null $value,
    ): EntityInterface|false {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            $entity->value = $value;
        } else {
            $entity = $this->newEntity([
                'game_id' => $gameId,
                'key' => $key,
                'value' => $value,
            ]);
        }

        return $this->save($entity);
    }

    /**
     * Delete an attribute for a game.
     *
     * @param int $gameId Game id.
     * @param string $key Attribute key.
     * @return bool True on success, false otherwise.
     */
    public function deleteAttribute(int $gameId, string $key): bool
    {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            return (bool)$this->delete($entity);
        }

        return false;
    }

    /**
     * Get EAV form template for sport-specific game entry
     *
     * @param int $sportId Sport ID
     * @param string $periods Number of periods from games.periods field (varchar)
     * @param string $overtime Number of OT periods from games.ot field (varchar)
     * @return array EAV field template for form generation
     */
    public function getEavTemplateForSport(int $sportId, string $periods = '2', string $overtime = '0'): array
    {
        $sportConfig = $this->getSportConfig($sportId);
        $template = [];

        // Convert varchar periods to integer for processing
        $periodsInt = (int)($periods ?: 2);
        $overtimeInt = (int)($overtime ?: 0);

        // Get appropriate period name based on sport and period count
        $periodName = $this->getPeriodName($sportConfig, $periodsInt);

        // Regular periods scoring
        for ($i = 1; $i <= $periodsInt; $i++) {
            $labelTeam = "{$periodName} {$i} - Team";
            $labelOpp = "{$periodName} {$i} - Opponent";
            $template["period_{$i}_team"] = [
                'field_name' => "period_{$i}_team",
                'display_label' => $labelTeam,
                // Backward compatibility alias for existing tests expecting 'label'
                'label' => $labelTeam,
                'field_type' => 'number',
                'field_group' => 'scoring',
                'min' => 0,
                'class' => 'form-control',
                'placeholder' => '0',
                'default_value' => null,
            ];
            $template["period_{$i}_opponent"] = [
                'field_name' => "period_{$i}_opponent",
                'display_label' => $labelOpp,
                'label' => $labelOpp,
                'field_type' => 'number',
                'field_group' => 'scoring',
                'min' => 0,
                'class' => 'form-control',
                'placeholder' => '0',
                'default_value' => null,
            ];
        }

        // Overtime periods
        if ($overtimeInt > 0) {
            $otName = $sportConfig['overtime_name'] ?? 'OT';
            for ($i = 1; $i <= $overtimeInt; $i++) {
                $labelTeam = "{$otName} {$i} - Team";
                $labelOpp = "{$otName} {$i} - Opponent";
                $template["overtime_{$i}_team"] = [
                    'field_name' => "overtime_{$i}_team",
                    'display_label' => $labelTeam,
                    'label' => $labelTeam,
                    'field_type' => 'number',
                    'field_group' => 'overtime',
                    'min' => 0,
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'default_value' => null,
                ];
                $template["overtime_{$i}_opponent"] = [
                    'field_name' => "overtime_{$i}_opponent",
                    'display_label' => $labelOpp,
                    'label' => $labelOpp,
                    'field_type' => 'number',
                    'field_group' => 'overtime',
                    'min' => 0,
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'default_value' => null,
                ];
            }
        }

        // Officials (vary by sport)
        $officials = $this->getOfficials($sportConfig);
        foreach ($officials as $i => $title) {
            $key = 'official_' . ($i + 1);
            $template[$key] = [
                'field_name' => $key,
                'display_label' => $title,
                'label' => $title,
                'field_type' => 'text',
                'field_group' => 'officials',
                'maxlength' => 100,
                'class' => 'form-control',
                'placeholder' => 'Official name',
                'default_value' => null,
            ];
        }

        return $template;
    }

    /**
     * Get sport configuration from sport_configs table
     *
     * @param int $sportId Sport ID
     * @return array Sport configuration
     */
    private function getSportConfig(int $sportId): array
    {
        // Get sport configs from database using raw SQL
        $connection = $this->getConnection();
        $query = 'SELECT config_key, config_value FROM sport_configs WHERE sport_id = ?';
        $statement = $connection->execute($query, [$sportId]);
        $results = $statement->fetchAll('assoc');
        $config = [];
        foreach ($results as $row) {
            $value = $row['config_value'];
            // Try to decode JSON values
            $decoded = json_decode($value, true);
            $config[$row['config_key']] = $decoded ?? $value;
        }

        // Fallback defaults if no config found
        if (empty($config)) {
            $config = [
                'period_name_2' => 'Half',
                'period_name_4' => 'Quarter',
                'overtime_name' => 'OT',
                'officials' => ['Official 1', 'Official 2'],
            ];
        }

        return $config;
    }

    /**
     * Get appropriate period name based on sport config and period count
     *
     * @param array $sportConfig Sport configuration
     * @param int $periods Number of periods
     * @return string Period name (Half, Quarter, Inning, etc.)
     */
    private function getPeriodName(array $sportConfig, int $periods): string
    {
        // Look for period-specific name first
        $periodKey = "period_name_{$periods}";
        if (isset($sportConfig[$periodKey])) {
            return $sportConfig[$periodKey];
        }

        // Basketball special handling: 2 = halves, 4 = quarters
        if ($periods == 2 && isset($sportConfig['period_name_2'])) {
            return $sportConfig['period_name_2']; // "Half"
        }
        if ($periods == 4 && isset($sportConfig['period_name_4'])) {
            return $sportConfig['period_name_4']; // "Quarter"
        }

        // Fallback to generic period name
        return 'Period';
    }

    /**
     * Get officials list for sport
     *
     * @param array $sportConfig Sport configuration
     * @return array Officials titles
     */
    private function getOfficials(array $sportConfig): array
    {
        if (isset($sportConfig['officials']) && is_array($sportConfig['officials'])) {
            return $sportConfig['officials'];
        }

        // Default officials
        return ['Official 1', 'Official 2'];
    }

    /**
     * Save bulk EAV attributes for a game
     *
     * @param int $gameId Game ID
     * @param array $eavData EAV data from form submission
     * @return bool Success status
     */
    public function saveBulkAttributes(int $gameId, array $eavData): bool
    {
        // Delete existing EAV for this game
        $this->deleteAll(['game_id' => $gameId]);

        $entities = [];
        foreach ($eavData as $key => $value) {
            // Only save non-empty values
            if ($value !== '' && $value !== null) {
                $entities[] = $this->newEntity([
                    'game_id' => $gameId,
                    'key' => $key,
                    'value' => (string)$value,
                ]);
            }
        }

        if (!empty($entities)) {
            $result = $this->saveMany($entities);

            return $result !== false;
        }

        return true;
    }

    /**
     * Get formatted scoring summary for display
     *
     * @param int $gameId Game ID
     * @param int $periods Number of periods
     * @param int $overtime Number of overtime periods
     * @return array Formatted scoring data
     */
    public function getFormattedScoring(int $gameId, int $periods = 2, int $overtime = 0): array
    {
        $attributes = $this->getAttributesForGame($gameId);
        $scoring = [
            'periods' => [],
            'overtime' => [],
            'totals' => ['team' => 0, 'opponent' => 0],
        ];

        // Regular periods
        for ($i = 1; $i <= $periods; $i++) {
            $teamScore = (int)($attributes["period_{$i}_team"] ?? 0);
            $oppScore = (int)($attributes["period_{$i}_opponent"] ?? 0);

            $scoring['periods'][$i] = [
                'team' => $teamScore,
                'opponent' => $oppScore,
            ];

            $scoring['totals']['team'] += $teamScore;
            $scoring['totals']['opponent'] += $oppScore;
        }

        // Overtime periods
        for ($i = 1; $i <= $overtime; $i++) {
            $teamScore = (int)($attributes["overtime_{$i}_team"] ?? 0);
            $oppScore = (int)($attributes["overtime_{$i}_opponent"] ?? 0);

            $scoring['overtime'][$i] = [
                'team' => $teamScore,
                'opponent' => $oppScore,
            ];

            $scoring['totals']['team'] += $teamScore;
            $scoring['totals']['opponent'] += $oppScore;
        }

        return $scoring;
    }
}
