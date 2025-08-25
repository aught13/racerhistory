<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * @method \App\Model\Entity\Opponent newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Opponent get($primaryKey, $options = [])
 * @method \App\Model\Entity\Opponent patchEntity(\App\Model\Entity\Opponent $entity, array $data, array $options = [])
 */
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
        /** @var int|null $currentId */
        $currentId = $entity->opponent_current ?? null;
        if ($currentId === null) {
            return true;
        }
        /** @var int|null $entityId */
        $entityId = $entity->id ?? null;
        if ($entityId !== null && $currentId == $entityId) {
            return false; // Cannot reference itself
        }

        return $this->exists(['id' => $currentId]);
    }
}
