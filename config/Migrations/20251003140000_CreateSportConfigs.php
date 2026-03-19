<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateSportConfigs migration
 *
 * Creates sport-specific configuration to support flexible period naming
 * and sport-specific features while preserving existing games table structure
 */
class CreateSportConfigs extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Apply migration: create sport configurations table
     *
     * @return void
     */
    public function up(): void
    {
        // Sport game configurations
        $table = $this->table('sport_configs', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'integer', ['autoIncrement' => true])
            ->addColumn('sport_id', 'integer', [
                'null' => false,
                'signed' => false,
                'comment' => 'References sports.id'
            ])
            ->addColumn('config_key', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Configuration key (e.g., period_name_2, period_name_4, officials)'
            ])
            ->addColumn('config_value', 'text', [
                'null' => true,
                'comment' => 'Configuration value (JSON for complex data)'
            ])
            ->addColumn('description', 'string', [
                'limit' => 200,
                'null' => true,
                'comment' => 'Human-readable description'
            ])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            // Foreign key commented out due to constraint issues - will add manually if needed
            // ->addForeignKey('sport_id', 'sports', 'id', [
            //     'delete' => 'CASCADE',
            //     'update' => 'CASCADE'
            // ])
            ->addIndex(['sport_id', 'config_key'], ['unique' => true])
            ->addIndex(['sport_id'])
            ->create();

        // Insert default configurations for common sports
        $this->_insertDefaultConfigs();
    }

    /**
     * Insert default sport configurations
     *
     * @return void
     */
    private function _insertDefaultConfigs(): void
    {
        $now = date('Y-m-d H:i:s');

        // Get existing sports (assuming they exist from your current setup)
        $sports = $this->fetchAll('SELECT id, sport_name FROM sports ORDER BY id');

        foreach ($sports as $sport) {
            $sportId = $sport['id'];
            $sportName = $sport['sport_name'];

            $configs = $this->_getConfigsForSport($sportName);

            foreach ($configs as $key => $value) {
                $this->table('sport_configs')->insert([
                    'sport_id' => $sportId,
                    'config_key' => $key,
                    'config_value' => is_array($value) ? json_encode($value) : $value,
                    'description' => $this->_getConfigDescription($key),
                    'created' => $now,
                    'modified' => $now
                ])->save();
            }
        }
    }

    /**
     * Get sport-specific configurations
     *
     * @param string $sportName Sport name
     * @return array Configuration array
     */
    private function _getConfigsForSport(string $sportName): array
    {
        $sportLower = strtolower($sportName);

        switch ($sportLower) {
            case 'basketball':
                return [
                    'period_name_2' => 'Half',           // For 2-period games
                    'period_name_4' => 'Quarter',        // For 4-period games
                    'overtime_name' => 'OT',
                    'default_periods' => '2',
                    'supports_periods' => json_encode([2, 4]),
                    'officials' => json_encode(['Referee 1', 'Referee 2', 'Official 3']),
                    'scoring_type' => 'cumulative'
                ];

            case 'football':
                return [
                    'period_name_4' => 'Quarter',
                    'overtime_name' => 'OT',
                    'default_periods' => '4',
                    'supports_periods' => json_encode([4]),
                    'officials' => json_encode(['Referee', 'Umpire', 'Head Linesman', 'Line Judge', 'Field Judge', 'Side Judge', 'Back Judge']),
                    'scoring_type' => 'cumulative'
                ];

            case 'baseball':
            case 'softball':
                return [
                    'period_name_9' => 'Inning',
                    'overtime_name' => 'Extra Inning',
                    'default_periods' => '9',
                    'supports_periods' => json_encode([9]),
                    'officials' => json_encode(['Home Plate', 'First Base', 'Second Base', 'Third Base']),
                    'scoring_type' => 'by_period'
                ];

            case 'soccer':
            case 'football (soccer)':
                return [
                    'period_name_2' => 'Half',
                    'overtime_name' => 'Extra Time',
                    'default_periods' => '2',
                    'supports_periods' => json_encode([2]),
                    'officials' => json_encode(['Referee', 'Assistant Referee 1', 'Assistant Referee 2', 'Fourth Official']),
                    'scoring_type' => 'cumulative'
                ];

            case 'hockey':
                return [
                    'period_name_3' => 'Period',
                    'overtime_name' => 'OT',
                    'default_periods' => '3',
                    'supports_periods' => json_encode([3]),
                    'officials' => json_encode(['Referee 1', 'Referee 2', 'Linesman 1', 'Linesman 2']),
                    'scoring_type' => 'cumulative'
                ];

            case 'volleyball':
                return [
                    'period_name_3' => 'Set',
                    'period_name_5' => 'Set',
                    'overtime_name' => 'Deciding Set',
                    'default_periods' => '3',
                    'supports_periods' => json_encode([3, 5]),
                    'officials' => json_encode(['First Referee', 'Second Referee', 'Scorer', 'Libero Tracker']),
                    'scoring_type' => 'by_period'
                ];

            default:
                return [
                    'period_name_2' => 'Period',
                    'overtime_name' => 'OT',
                    'default_periods' => '2',
                    'supports_periods' => json_encode([2]),
                    'officials' => json_encode(['Official 1', 'Official 2']),
                    'scoring_type' => 'cumulative'
                ];
        }
    }

    /**
     * Get configuration description
     *
     * @param string $key Configuration key
     * @return string Description
     */
    private function _getConfigDescription(string $key): string
    {
        $descriptions = [
            'period_name_2' => 'Period name for 2-period games',
            'period_name_3' => 'Period name for 3-period games',
            'period_name_4' => 'Period name for 4-period games',
            'period_name_5' => 'Period name for 5-period games',
            'period_name_9' => 'Period name for 9-period games',
            'overtime_name' => 'Name for overtime periods',
            'default_periods' => 'Default number of periods for this sport',
            'supports_periods' => 'JSON array of supported period counts',
            'officials' => 'JSON array of official titles',
            'scoring_type' => 'How scoring works: cumulative or by_period'
        ];

        return $descriptions[$key] ?? 'Sport configuration value';
    }

    /**
     * Rollback migration
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('sport_configs')->drop()->save();
    }
}
