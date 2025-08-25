<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSiteOptionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('site_options');
        $table
            ->addColumn('option_key', 'string', [
                'limit' => 100,
                'null' => false
            ])
            ->addIndex(['option_key'], ['unique' => true])
            ->addColumn('value', 'text', [
                'null' => true
            ])
            # Use datetime for compatibility with Cake's TimestampBehavior across drivers
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->create();
    }
}
