<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * EnhanceGamesTableForMultiSport migration
 *
 * Adds minimal enhancements to games table for multi-sport support
 * while preserving all existing data and structure
 */
class EnhanceGamesTableForMultiSport extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Apply migration: add optional multi-sport fields to games table
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('games');

        // Add game status tracking (useful for all sports)
        if (!$table->hasColumn('game_status')) {
            $table->addColumn('game_status', 'string', [
                'limit' => 20,
                'default' => 'completed', // Default to completed for existing games
                'null' => false,
                'after' => 'post',
                'comment' => 'scheduled, in_progress, completed, cancelled, postponed, suspended'
            ]);
        }

        // Add weather conditions (for outdoor sports)
        if (!$table->hasColumn('weather_conditions')) {
            $table->addColumn('weather_conditions', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'attendance',
                'comment' => 'Weather description for outdoor games'
            ]);
        }

        // Add surface/court type
        if (!$table->hasColumn('surface_type')) {
            $table->addColumn('surface_type', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'weather_conditions',
                'comment' => 'Playing surface (indoor, outdoor, grass, turf, court, field, etc.)'
            ]);
        }

        $table->update();
    }

    /**
     * Rollback migration: remove added fields
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('games');

        if ($table->hasColumn('surface_type')) {
            $table->removeColumn('surface_type');
        }
        if ($table->hasColumn('weather_conditions')) {
            $table->removeColumn('weather_conditions');
        }
        if ($table->hasColumn('game_status')) {
            $table->removeColumn('game_status');
        }

        $table->update();
    }
}
