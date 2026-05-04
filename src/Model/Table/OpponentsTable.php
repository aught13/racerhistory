<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * @method \App\Model\Entity\Opponent newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Opponent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Opponent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @property \App\Model\Table\PlacesTable&\Cake\ORM\Association\BelongsTo $Places
 * @property \App\Model\Table\OpponentsTable&\Cake\ORM\Association\BelongsTo $CurrentOpponent
 * @method \App\Model\Entity\Opponent newEmptyEntity()
 * @method \App\Model\Entity\Opponent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Opponent findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Opponent[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Opponent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Opponent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Opponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Opponent>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Opponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Opponent> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Opponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Opponent>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Opponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Opponent> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
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
