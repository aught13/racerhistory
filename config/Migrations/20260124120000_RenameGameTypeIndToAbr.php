<?php
/**
 * Contributor Note: The `$autoId` property type must match your local CakePHP version's AbstractMigration parent class.
 * CI will auto-adapt this property type as needed (see .github/workflows/ci.yml, step: "Fix migration compatibility").
 * Do not attempt runtime adaptation in migration code; rely on CI logic.
 */

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * RenameGameTypeIndToAbr migration
 *
 * Renames game_types.ind to game_types.abr and expands length to 6 characters.
 */
class RenameGameTypeIndToAbr extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Apply migration: rename ind to abr and update length.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('game_types');
        $table->renameColumn('ind', 'abr')->update();
        $table->changeColumn('abr', 'string', [
            'default' => null,
            'limit' => 6,
            'null' => true,
        ])->update();
    }

    /**
     * Revert migration: rename abr back to ind and restore length.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('game_types');
        $table->changeColumn('abr', 'string', [
            'default' => null,
            'limit' => 2,
            'null' => true,
        ])->update();
        $table->renameColumn('abr', 'ind')->update();
    }
}
