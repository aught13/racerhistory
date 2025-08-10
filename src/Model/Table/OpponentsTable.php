<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

class OpponentsTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('opponents');
        $this->setPrimaryKey('id');
        $this->setDisplayField('opponent_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Places', [
            'foreignKey' => 'place_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('CurrentOpponent', [
            'className' => 'Opponents',
            'foreignKey' => 'opponent_current',
            'propertyName' => 'current_opponent',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Validate that opponent_current references a different existing opponent or is null.
     *
     * @param \Cake\Datasource\EntityInterface $entity Opponent entity.
     * @return bool True if valid, false otherwise.
     */
    public function validateOpponentCurrent(EntityInterface $entity): bool
    {
        $currentId = $entity->opponent_current;
        if ($currentId === null) {
            return true;
        }
        if ($entity->id && $currentId == $entity->id) {
            return false; // Cannot reference itself
        }

        return $this->exists(['id' => $currentId]);
    }
}
