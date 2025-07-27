<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class OpponentsTable extends Table
{
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
     * Validates that opponent_current is not itself and exists in the table
     */
    public function validateOpponentCurrent($entity)
    {
        $currentId = $entity->opponent_current;
        if ($currentId === null) {
            return true;
        }
        if ($entity->id && $currentId == $entity->id) {
            return false; // Cannot reference itself
        }
        $exists = $this->exists(['id' => $currentId]);
        return $exists;
    }
}
