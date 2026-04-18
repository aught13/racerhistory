<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * RenamePlaceNameToPlaceCountry migration
 *
 * Renames place_name to place_country (ISO 3166 alpha-3 code).
 * place_city and place_state remain unchanged; place_country and place_city become required.
 */
class RenamePlaceNameToPlaceCountry extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Apply migration: rename place_name to place_country.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('places');
        $table->renameColumn('place_name', 'place_country');
        $table->update();
    }

    /**
     * Reverse migration: rename place_country back to place_name.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('places');
        $table->renameColumn('place_country', 'place_name');
        $table->update();
    }
}
