<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddImageTagsAndUsages extends AbstractMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        // image_tags
        $this->table('image_tags')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // images_image_tags (join table)
        $this->table('images_image_tags')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('image_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('image_tag_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['image_id'])
            ->addIndex(['image_tag_id'])
            ->addIndex(['image_id', 'image_tag_id'], ['unique' => true])
            ->create();

        // image_usages (tracks where images are referenced)
        $this->table('image_usages')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('image_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('model', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('foreign_key', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('context', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('field', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['image_id'])
            ->addIndex(['model', 'foreign_key'])
            ->create();
    }

    public function down(): void
    {
        $this->table('images_image_tags')->drop()->save();
        $this->table('image_usages')->drop()->save();
        $this->table('image_tags')->drop()->save();
    }
}
