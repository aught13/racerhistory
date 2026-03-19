<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddImageTagsAndUsages extends BaseMigration
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

        // Update image_usages table to add context field if it doesn't exist
        if ($this->hasTable('image_usages')) {
            $table = $this->table('image_usages');
            if (!$table->hasColumn('context')) {
                $table->addColumn('context', 'string', ['limit' => 80, 'null' => true, 'default' => null])
                    ->update();
            }
        }
    }

    public function down(): void
    {
        $this->table('images_image_tags')->drop()->save();
        if ($this->hasTable('image_usages')) {
            $table = $this->table('image_usages');
            if ($table->hasColumn('context')) {
                $table->removeColumn('context')->update();
            }
        }
        $this->table('image_tags')->drop()->save();
    }
}
