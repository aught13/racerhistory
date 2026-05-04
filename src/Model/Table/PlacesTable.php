<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @method \App\Model\Entity\Place newEmptyEntity()
 * @method \App\Model\Entity\Place newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Place[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Place get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Place findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Place patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Place[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Place|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Place saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Place[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Place>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Place[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Place> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Place[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Place>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Place[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Place> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class PlacesTable extends Table
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
        $this->setTable('places');
        $this->setPrimaryKey('id');
        $this->setDisplayField('place_city');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
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
            ->scalar('place_country')
            ->maxLength('place_country', 3)
            ->requirePresence('place_country', 'create')
            ->notEmptyString('place_country');

        $validator
            ->scalar('place_city')
            ->maxLength('place_city', 162)
            ->requirePresence('place_city', 'create')
            ->notEmptyString('place_city');

        $validator
            ->scalar('place_state')
            ->maxLength('place_state', 162)
            ->allowEmptyString('place_state');

        return $validator;
    }

    /**
     * Application rules.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker instance.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addCreate(
            $rules->isUnique(
                ['place_country', 'place_city', 'place_state'],
                'A place with that country, city, and state already exists.',
            ),
        );

        return $rules;
    }
}
