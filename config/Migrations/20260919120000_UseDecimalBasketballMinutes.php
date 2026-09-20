<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Store basketball minutes with seconds-derived decimal precision.
 */
class UseDecimalBasketballMinutes extends BaseMigration
{
    /**
     * @var bool Disable automatic primary key generation
     */
    public bool $autoId = false;

    /**
     * Change basketball MIN columns to DECIMAL(6,2).
     *
     * @return void
     */
    public function up(): void
    {
        foreach ($this->minuteTables() as $tableName) {
            $this->execute("UPDATE `{$tableName}` SET `MIN` = NULL WHERE CAST(`MIN` AS CHAR) = ''");
            $this->table($tableName)->changeColumn('MIN', 'decimal', [
                'precision' => 6,
                'scale' => 2,
                'default' => null,
                'null' => true,
            ])->update();
        }
    }

    /**
     * Restore the original string-backed minute columns.
     *
     * @return void
     */
    public function down(): void
    {
        foreach ($this->minuteTables() as $tableName) {
            $limit = $tableName === 'stat_basket_game_person' ? 3 : 11;
            $this->table($tableName)->changeColumn('MIN', 'string', [
                'limit' => $limit,
                'default' => null,
                'null' => true,
            ])->update();
        }
    }

    /**
     * @return list<string> Basketball tables containing MIN
     */
    private function minuteTables(): array
    {
        return [
            'stat_basket_game_box',
            'stat_basket_game_opponent',
            'stat_basket_game_person',
            'stat_basket_season_opponent',
            'stat_basket_season_person',
        ];
    }
}
