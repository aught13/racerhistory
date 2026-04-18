<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * AddGpMinToGameBox migration
 *
 * Adds GP (games played) and MIN (team minutes) columns to stat_basket_game_box
 * so that season totals updates can properly track games played and minutes.
 */
class AddGpMinToGameBox extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Apply migration: add GP and MIN columns.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('stat_basket_game_box');
        $table->addColumn('GP', 'string', [
            'default' => null,
            'limit' => 11,
            'null' => true,
            'after' => 'period',
        ])->addColumn('MIN', 'string', [
            'default' => null,
            'limit' => 11,
            'null' => true,
            'after' => 'GP',
        ])->update();
    }

    /**
     * Revert migration: remove GP and MIN columns.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('stat_basket_game_box');
        $table->removeColumn('GP')
            ->removeColumn('MIN')
            ->update();
    }
}
