<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Remove the legacy biography field from persons.
 */
class RemoveBioFromPersons extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('persons')) {
            $table = $this->table('persons');
            if ($table->hasColumn('bio')) {
                $table->removeColumn('bio')->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('persons')) {
            $table = $this->table('persons');
            if (!$table->hasColumn('bio')) {
                $table->addColumn('bio', 'text', [
                    'null' => true,
                ])->update();
            }
        }
    }
}
