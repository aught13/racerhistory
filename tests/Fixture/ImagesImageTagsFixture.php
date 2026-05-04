<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\Fixture\TestFixture;

class ImagesImageTagsFixture extends TestFixture
{
    public string $table = 'images_image_tags';

    /**
     * Runs the insert routine.
     *
     * @param ConnectionInterface $connection
     */
    public function insert(ConnectionInterface $connection): bool
    {
        // Ensure deterministic IDs across runs to avoid UNIQUE constraint errors
        $connection->disableConstraints(function () use ($connection) {
            $connection->execute('DELETE FROM ' . $connection->getDriver()->quoteIdentifier($this->table));
            if ($connection->getDriver() instanceof Sqlite) {
                $connection->execute('DELETE FROM sqlite_sequence WHERE name = :name', ['name' => $this->table]);
            }
        });

        return parent::insert($connection);
    }

    /**
     * Initializes the fixture data.
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'image_id' => 1,
                'image_tag_id' => 1,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'image_id' => 1,
                'image_tag_id' => 3,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];
        parent::init();
    }
}
