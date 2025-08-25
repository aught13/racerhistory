<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateImagesTable migration
 */
class CreateImagesTable extends AbstractMigration
{
    /**
     * Disable automatic id field; we add it manually for portability.
     */
    public bool $autoId = false;
    /**
     * Apply migration: create images table.
     */
    public function up(): void
    {
        if (!$this->hasTable('images')) {
            $this->table('images')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'null' => false,
                    'signed' => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('filename', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('storage_subdir', 'string', ['limit' => 16, 'null' => true, 'default' => null, 'comment' => 'Legacy year/month subdir (deprecated)'])
                ->addColumn('storage_path', 'string', ['limit' => 190, 'null' => false, 'default' => '', 'comment' => 'Relative path: year/month/filename'])
                ->addColumn('original_name', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('mime', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('ext', 'string', ['limit' => 12, 'null' => true])
                ->addColumn('byte_size', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('width', 'integer', ['null' => true])
                ->addColumn('height', 'integer', ['null' => true])
                ->addColumn('variants', 'text', ['null' => true, 'comment' => 'JSON encoded variant metadata'])
                ->addColumn('hash', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'active'])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addColumn('modified', 'datetime', ['null' => false])
                ->addIndex(['hash'], ['unique' => true])
                ->addIndex(['status'])
                ->create();
        }
    }

    /**
     * Revert migration.
     */
    public function down(): void
    {
        if ($this->hasTable('images')) {
            $this->table('images')->drop()->save();
        }
    }
}
