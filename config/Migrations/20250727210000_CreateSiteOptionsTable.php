<?php
declare(strict_types=1);
use Migrations\AbstractMigration;

class CreateSiteOptionsTable extends AbstractMigration
{
    public function change(): void
    {
        $driver = $this->getAdapter()->getAdapterType();
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
            ->addColumn('created', $driver === 'sqlite' ? 'text' : 'datetime', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'null' => false
            ])
            ->addColumn('modified', $driver === 'sqlite' ? 'text' : 'datetime', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'null' => false
            ])
            ->create();
    }
}