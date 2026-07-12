<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * AddPlaceCityToPlaces migration
 *
 * Adds the `place_city` column to `places` so existing code and tests
 * that select `Places.place_city` do not fail on older DBs.
 */
class AddPlaceCityToPlaces extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        $table = $this->table('places');
        if (!$table->hasColumn('place_city')) {
            $table->addColumn('place_city', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ]);
            $table->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('places');
        if ($table->hasColumn('place_city')) {
            $table->removeColumn('place_city');
            $table->update();
        }
    }
}
