<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Database\Schema\TableSchema;
use Cake\ORM\Query;

class GameEavTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_eav');
        $this->setPrimaryKey('id');
        $this->setDisplayField('key');
    }

    /**
     * Get all attributes for a game_id as key-value pairs
     */
    public function getAttributesForGame($gameId): array
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
     * Add or update an attribute for a game
     */
    public function setAttribute($gameId, $key, $value)
    {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            $entity->value = $value;
        } else {
            $entity = $this->newEntity([
                'game_id' => $gameId,
                'key' => $key,
                'value' => $value
            ]);
        }
        return $this->save($entity);
    }

    /**
     * Delete an attribute for a game
     */
    public function deleteAttribute($gameId, $key)
    {
        $entity = $this->find()
            ->where(['game_id' => $gameId, 'key' => $key])
            ->first();
        if ($entity) {
            return $this->delete($entity);
        }
        return false;
    }
}
