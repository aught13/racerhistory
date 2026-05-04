<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\GamesTable&\Cake\ORM\Association\BelongsTo $Games
 * @method \App\Model\Entity\StatBasketGameOpponent newEmptyEntity()
 * @method \App\Model\Entity\StatBasketGameOpponent newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatBasketGameOpponent findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameOpponent>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameOpponent> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameOpponent>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameOpponent[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameOpponent> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class StatBasketGameOpponentTable extends Table
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
        $this->setTable('stat_basket_game_opponent');
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
            ->scalar('period')
            ->maxLength('period', 10)
            ->allowEmptyString('period');

        $validator
            ->scalar('name')
            ->maxLength('name', 162)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('jersey')
            ->maxLength('jersey', 11)
            ->allowEmptyString('jersey');

        $validator
            ->scalar('position')
            ->maxLength('position', 11)
            ->allowEmptyString('position');

        // GP (games played) defaults to 1
        $validator
            ->scalar('GP')
            ->numeric('GP', 'Must be a numeric value')
            ->allowEmptyString('GP');

        // GS (game started) is a checkbox (1 or NULL)
        $validator
            ->scalar('GS')
            ->numeric('GS', 'Must be a numeric value')
            ->allowEmptyString('GS');

        // PTS is required for opponent stats
        $validator
            ->scalar('PTS')
            ->numeric('PTS', 'Must be a numeric value')
            ->requirePresence('PTS', 'create')
            ->notEmptyString('PTS');

        // All other stat fields are optional
        $optionalFields = [
            'MIN', 'FGM', 'FGA', 'TPM', 'TPA',
            'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'AST', 'STL',
            'BS', 'BD', 'TRN', 'PF', 'TF', 'FD',
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
        $rules->add($rules->existsIn(['game_id'], 'Games'));

        return $rules;
    }
}
