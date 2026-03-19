<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateImageUsagesTable migration
 */
class CreateImageUsagesTable extends BaseMigration
{
    /**
     * Disable automatic id field; we define it manually.
     */
    public bool $autoId = false;
    /**
     * Apply migration: create image_usages table.
     */
    public function up(): void
    {
        if (!$this->hasTable('image_usages')) {
            $this->table('image_usages')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'null' => false,
                    'signed' => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('image_id', 'integer', ['null' => false])
                ->addColumn('model', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('foreign_key', 'integer', ['null' => false])
                ->addColumn('field', 'string', ['limit' => 60, 'null' => false])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addIndex(['image_id'])
                ->addIndex(['model', 'foreign_key'])
                ->create();
        }
    }

    /**
     * Revert migration.
     */
    public function down(): void
    {
        if ($this->hasTable('image_usages')) {
            $this->table('image_usages')->drop()->save();
        }
    }
}
