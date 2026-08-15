<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * AddSportKeyToTeams migration
 *
 * Transitional step for Sports model retirement. Adds canonical `sport_key`
 * to teams, backfills from legacy `sport_id`, and keeps `sport_id` intact.
 */
class AddSportKeyToTeams extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        $table = $this->table('teams');

        if (!$table->hasColumn('sport_key')) {
            $table->addColumn('sport_key', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
                'after' => 'sport_id',
            ]);
            $table->update();
        }

        $this->execute(
            "UPDATE teams
            SET sport_key = CASE sport_id
                WHEN 1 THEN 'basketball'
                WHEN 2 THEN 'football'
                WHEN 3 THEN 'baseball'
                ELSE sport_key
            END
            WHERE sport_key IS NULL OR sport_key = ''",
        );

        $table = $this->table('teams');
        if (!$table->hasIndex(['sport_key'])) {
            $table->addIndex(['sport_key'], ['name' => 'idx_teams_sport_key']);
            $table->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('teams');

        if ($table->hasIndex(['sport_key'])) {
            $table->removeIndex(['sport_key']);
            $table->update();
        }

        if ($table->hasColumn('sport_key')) {
            $table->removeColumn('sport_key');
            $table->update();
        }
    }
}
