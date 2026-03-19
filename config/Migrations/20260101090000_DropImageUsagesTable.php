<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class DropImageUsagesTable extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        if ($this->hasTable('image_usages')) {
            $this->table('image_usages')->drop()->save();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('image_usages')) {
            return;
        }

        $this->table('image_usages')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('image_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('model', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('foreign_key', 'integer', ['null' => false])
            ->addColumn('context', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('field', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['image_id'])
            ->addIndex(['model', 'foreign_key'])
            ->create();
    }
}
