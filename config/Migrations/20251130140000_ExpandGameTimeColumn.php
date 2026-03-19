<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Expand game_time column to accommodate HH:MM:SS format
 *
 * The game_time column was VARCHAR(5) which only fits HH:MM format.
 * Expanding to VARCHAR(8) to properly store HH:MM:SS values like '16:30:00'.
 */
class ExpandGameTimeColumn extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('games');
        $table->changeColumn('game_time', 'string', [
            'default' => null,
            'limit' => 8,
            'null' => true,
        ])
        ->update();
    }
}
