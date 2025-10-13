<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SportStatRegistry Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Sports
 * @method \App\Model\Entity\SportStatRegistry newEmptyEntity()
 * @method \App\Model\Entity\SportStatRegistry newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\SportStatRegistry> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SportStatRegistry get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SportStatRegistry findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SportStatRegistry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\SportStatRegistry> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SportStatRegistry|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SportStatRegistry saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\SportStatRegistry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportStatRegistry>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SportStatRegistry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportStatRegistry> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SportStatRegistry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportStatRegistry>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SportStatRegistry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportStatRegistry> deleteManyOrFail(iterable $entities, array $options = [])
 */
class SportStatRegistryTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sport_stat_registry');
        $this->setDisplayField('display_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
            'joinType' => 'INNER',
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
            ->integer('sport_id')
            ->notEmptyString('sport_id', 'Sport ID is required');

        $validator
            ->scalar('context')
            ->maxLength('context', 20)
            ->notEmptyString('context', 'Context is required')
            ->inList('context', ['game', 'season', 'career'], 'Context must be game, season, or career');

        $validator
            ->scalar('entity_type')
            ->maxLength('entity_type', 20)
            ->notEmptyString('entity_type', 'Entity type is required')
            ->inList('entity_type', ['team', 'player', 'opponent', 'box'], 'Entity type must be team, player, opponent, or box');

        $validator
            ->scalar('table_name')
            ->maxLength('table_name', 100)
            ->notEmptyString('table_name', 'Table name is required')
            ->regex('table_name', '/^[a-z_]+$/', 'Table name must contain only lowercase letters and underscores');

        $validator
            ->scalar('display_name')
            ->maxLength('display_name', 100)
            ->notEmptyString('display_name', 'Display name is required');

        $validator
            ->scalar('field_mapping')
            ->allowEmptyString('field_mapping')
            ->add('field_mapping', 'validJson', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return true;
                    }

                    json_decode($value);

                    return json_last_error() === JSON_ERROR_NONE;
                },
                'message' => 'Field mapping must be valid JSON',
            ]);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('sport_id', 'Sports'), ['errorField' => 'sport_id']);
        $rules->add($rules->isUnique(['sport_id', 'context', 'entity_type']), ['errorField' => 'context', 'message' => 'This combination of sport, context, and entity type already exists']);

        return $rules;
    }

    /**
     * Find stat tables for a given sport
     *
     * @param \Cake\ORM\Query $query The query builder
     * @param array $options Options array with 'sport_id'
     * @return \Cake\ORM\Query
     */
    public function findBySport(\Cake\ORM\Query $query, array $options): \Cake\ORM\Query
    {
        return $query->where(['sport_id' => $options['sport_id']]);
    }

    /**
     * Find stat tables for a specific context (game, season, career)
     *
     * @param \Cake\ORM\Query $query The query builder
     * @param array $options Options array with 'context'
     * @return \Cake\ORM\Query
     */
    public function findByContext(\Cake\ORM\Query $query, array $options): \Cake\ORM\Query
    {
        return $query->where(['context' => $options['context']]);
    }

    /**
     * Find stat tables for a specific entity type (team, player, opponent, box)
     *
     * @param \Cake\ORM\Query $query The query builder
     * @param array $options Options array with 'entity_type'
     * @return \Cake\ORM\Query
     */
    public function findByEntityType(\Cake\ORM\Query $query, array $options): \Cake\ORM\Query
    {
        return $query->where(['entity_type' => $options['entity_type']]);
    }
}
