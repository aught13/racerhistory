<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateImagesTable migration
 */
class CreateImagesTable extends AbstractMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        if (!$this->hasTable('images')) {
            $this->table('images')
                ->addColumn('filename', 'string', ['limit' => 255, 'null' => false])
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

    public function down(): void
    {
        if ($this->hasTable('images')) {
            $this->table('images')->drop()->save();
        }
    }
}
