<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

class GameEavTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_eav');
        $this->setPrimaryKey('id');
        $this->setDisplayField('key');
    }

    /**
     * Get all attributes for a game as key => value pairs.
     *
     * @param int $gameId Game id.
     * @return array<string, mixed> Key/value attribute list.
     */
    public function getAttributesForGame(int $gameId): array
    {
        $rows = $this->find()
            ->select(['key', 'value'])
            ->where(['game_id' => $gameId])
            ->all();
        $attributes = [];
        foreach ($rows as $row) {
            $attributes[$row->key] = $row->value;
        }

        return $attributes;
    }

    /**
     * Add or update an attribute for a game.
     *
     * @param int $gameId Game id.
     * @param string $key Attribute key.
     * @param scalar|null $value Attribute value.
     * @return \Cake\Datasource\EntityInterface|false Saved entity or false on failure.
     */
    public function setAttribute(
        int $gameId,
        string $key,
        int|float|string|bool|null $value,
    ): EntityInterface|false {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            $entity->value = $value;
        } else {
            $entity = $this->newEntity([
                'game_id' => $gameId,
                'key' => $key,
                'value' => $value,
            ]);
        }

        return $this->save($entity);
    }

    /**
     * Delete an attribute for a game.
     *
     * @param int $gameId Game id.
     * @param string $key Attribute key.
     * @return bool True on success, false otherwise.
     */
    public function deleteAttribute(int $gameId, string $key): bool
    {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            return (bool)$this->delete($entity);
        }

        return false;
    }
}
