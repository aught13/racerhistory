<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\GamesTable&\Cake\ORM\Association\BelongsTo $Games
 * @method \App\Model\Entity\StatBasketGameTeam newEmptyEntity()
 * @method \App\Model\Entity\StatBasketGameTeam newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatBasketGameTeam findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameTeam>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameTeam> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameTeam>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameTeam[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameTeam> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class StatBasketGameTeamTable extends Table
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
        $this->setTable('stat_basket_game_team');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Games', [
            'foreignKey' => 'game_id',
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
            ->integer('game_id')
            ->requirePresence('game_id', 'create')
            ->notEmptyString('game_id');

        $validator
            ->boolean('opp')
            ->requirePresence('opp', 'create')
            ->inList('opp', [false, true], 'Must be false (team) or true (opponent)');

        // All stat fields are strings but should contain numeric values
        $numericFields = ['ORB', 'DRB', 'RB', 'TRN', 'TF', 'PTS'];

        foreach ($numericFields as $field) {
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
        $rules->add($rules->existsIn(['game_id'], 'Games'));

        return $rules;
    }
}
