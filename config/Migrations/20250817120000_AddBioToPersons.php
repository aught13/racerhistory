<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddBioToPersons migration
 */
class AddBioToPersons extends AbstractMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        if ($this->hasTable('persons')) {
            $table = $this->table('persons');
            if (!$table->hasColumn('bio')) {
                $type = 'text'; // "longtext" not directly mapped; text suffices, MySQL will use LONGTEXT if specified via raw SQL
                $table->addColumn('bio', $type, [
                    'null' => true,
                    'limit' => null,
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('persons')) {
            $table = $this->table('persons');
            if ($table->hasColumn('bio')) {
                $table->removeColumn('bio')->update();
            }
        }
    }
}
