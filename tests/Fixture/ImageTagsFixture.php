<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\Fixture\TestFixture;

class ImageTagsFixture extends TestFixture
{
    public string $table = 'image_tags';

    public function insert(ConnectionInterface $connection): bool
    {
        // Ensure a clean slate even if prior tests left rows behind
        $connection->disableConstraints(function () use ($connection) {
            $connection->execute('DELETE FROM ' . $connection->getDriver()->quoteIdentifier($this->table));
            if ($connection->getDriver() instanceof \Cake\Database\Driver\Sqlite) {
                $connection->execute('DELETE FROM sqlite_sequence WHERE name = :name', ['name' => $this->table]);
            }
        });

        return parent::insert($connection);
    }

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Person 1',
                'slug' => 'person-1',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'name' => 'Team Season 1',
                'slug' => 'teamseason-1',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'name' => 'Roster',
                'slug' => 'roster',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
