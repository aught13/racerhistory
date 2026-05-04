<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\TeamSeasonRostersTable&\Cake\ORM\Association\BelongsTo $TeamSeasonRosters
 * @method \App\Model\Entity\StatBasketSeasonPerson newEmptyEntity()
 * @method \App\Model\Entity\StatBasketSeasonPerson newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatBasketSeasonPerson findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonPerson>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonPerson> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonPerson>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonPerson[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonPerson> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class StatBasketSeasonPersonTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('stat_basket_season_person');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeasonRosters', [
            'foreignKey' => 'team_season_roster_id',
            'joinType' => 'LEFT',
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
            ->integer('team_season_roster_id')
            ->requirePresence('team_season_roster_id', 'create')
            ->notEmptyString('team_season_roster_id');

        // All stat fields are optional
        $optionalFields = [
            'GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA',
            'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'AST', 'STL',
            'BS', 'TRN', 'PF', 'TF',
        ];

        foreach ($optionalFields as $field) {
            $validator
                ->scalar($field)
                ->numeric($field, 'Must be a numeric value')
                ->allowEmptyString($field);
        }

        // PTS is int type
        $validator
            ->integer('PTS')
            ->allowEmptyString('PTS');

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
        $rules->add($rules->existsIn(['team_season_roster_id'], 'TeamSeasonRosters'));

        return $rules;
    }
}
