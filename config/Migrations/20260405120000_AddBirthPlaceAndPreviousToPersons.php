<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * AddBirthPlaceAndPreviousToPersons migration
 *
 * Adds birth_place_id (FK to places) and person_previous (previous school) columns to persons table.
 */
class AddBirthPlaceAndPreviousToPersons extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Apply migration: add birth_place_id and person_previous columns.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('persons');
        $table->addColumn('birth_place_id', 'integer', [
            'default' => null,
            'null' => true,
            'signed' => false,
            'after' => 'death',
        ])->addColumn('person_previous', 'string', [
            'default' => null,
            'limit' => 162,
            'null' => true,
            'after' => 'birth_place_id',
        ])->addIndex(['birth_place_id'], [
            'name' => 'idx_persons_birth_place_id',
        ])->addForeignKey('birth_place_id', 'places', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE',
            'constraint' => 'fk_persons_birth_place_id',
        ])->update();
    }

    /**
     * Revert migration: remove birth_place_id and person_previous columns.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('persons');
        $table->dropForeignKey('birth_place_id')
            ->removeIndex(['birth_place_id'])
            ->removeColumn('birth_place_id')
            ->removeColumn('person_previous')
            ->update();
    }
}
