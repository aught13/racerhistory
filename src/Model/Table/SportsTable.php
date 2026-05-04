<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sports Model
 *
 * Manages sports data for the application's historical sports information and statistics.
 *
 * @method \App\Model\Entity\Sport newEmptyEntity()
 * @method \App\Model\Entity\Sport newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Sport[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Sport get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Sport get($primaryKey, $contain = [])
 * @method \App\Model\Entity\Sport findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Sport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Sport[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Sport|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Sport saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Sport>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Sport> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Sport>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Sport[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Sport> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * Table Fields:
 * - id: Primary key, auto-increment integer
 * - sport_name: Name of the sport (max 162 chars, unique, required)
 * - created_at: Timestamp when record was created
 * - updated_at: Timestamp when record was last modified
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\HasMany $Teams
 * @property \App\Model\Table\SportConfigsTable&\Cake\ORM\Association\HasMany $SportConfigs
 * @property \App\Model\Table\SportStatRegistryTable&\Cake\ORM\Association\HasMany $SportStatRegistry
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class SportsTable extends Table
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
        $this->setTable('sports');
        $this->setPrimaryKey('id');
        $this->setDisplayField('sport_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);

        // Associations
        $this->hasMany('Teams', [
            'foreignKey' => 'sport_id',
            'dependent' => true,
        ]);

        $this->hasMany('SportConfigs', [
            'foreignKey' => 'sport_id',
            'dependent' => true,
        ]);

        $this->hasMany('SportStatRegistry', [
            'foreignKey' => 'sport_id',
            'dependent' => true,
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('sport_name')
            ->maxLength('sport_name', 162)
            ->requirePresence('sport_name', 'create')
            ->notEmptyString('sport_name')
            ->add('sport_name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator;
    }
}
