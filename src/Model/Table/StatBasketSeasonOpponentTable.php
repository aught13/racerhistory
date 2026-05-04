<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\TeamSeasonsTable&\Cake\ORM\Association\BelongsTo $TeamSeasons
 * @method \App\Model\Entity\StatBasketSeasonOpponent newEmptyEntity()
 * @method \App\Model\Entity\StatBasketSeasonOpponent newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatBasketSeasonOpponent findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonOpponent>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonOpponent> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonOpponent>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketSeasonOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketSeasonOpponent> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class StatBasketSeasonOpponentTable extends Table
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
        $this->setTable('stat_basket_season_opponent');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeasons', [
            'foreignKey' => 'team_season_id',
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
            ->integer('team_season_id')
            ->requirePresence('team_season_id', 'create')
            ->notEmptyString('team_season_id');

        // All stat fields are optional
        $optionalFields = [
            'GP', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA',
            'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'AST', 'STL',
            'BS', 'TRN', 'PF', 'TF', 'PTS',
        ];

        foreach ($optionalFields as $field) {
            $validator
                ->scalar($field)
                ->numeric($field, 'Must be a numeric value')
                ->allowEmptyString($field);
        }

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
        $rules->add($rules->existsIn(['team_season_id'], 'TeamSeasons'));

        return $rules;
    }
}
