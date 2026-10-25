<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Expand the opponent abbreviation field to 30 characters.
 */
class ExpandOpponentAbbrToThirty extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Apply the expanded opponent abbreviation limit.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('opponents')->changeColumn('opponent_abbr', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => true,
        ])->update();
    }

    /**
     * Restore the original opponent abbreviation limit.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('opponents')->changeColumn('opponent_abbr', 'string', [
            'default' => null,
            'limit' => 6,
            'null' => true,
        ])->update();
    }
}
