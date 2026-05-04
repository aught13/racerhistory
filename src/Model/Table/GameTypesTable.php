<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GameTypes Table
 *
 * @property \App\Model\Table\GamesTable&\Cake\ORM\Association\HasMany $Games
 * @method \App\Model\Entity\GameType newEmptyEntity()
 * @method \App\Model\Entity\GameType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\GameType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\GameType get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\GameType findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\GameType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\GameType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\GameType|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\GameType saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\GameType[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GameType>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\GameType[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GameType> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\GameType[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GameType>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\GameType[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\GameType> deleteManyOrFail(iterable $entities, array $options = [])
 */
class GameTypesTable extends Table
{
    /**
     * Initialize table configuration, behaviors and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_types');
        $this->setPrimaryKey('id');
        $this->setDisplayField('game_type_name');
        $this->hasMany('Games', [
            'foreignKey' => 'game_type_id',
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
            ->scalar('game_type_name')
            ->maxLength('game_type_name', 162)
            ->requirePresence('game_type_name', 'create')
            ->notEmptyString('game_type_name', 'Game type name is required.');

        $validator
            ->boolean('post')
            ->allowEmptyString('post');

        $validator
            ->boolean('conf')
            ->allowEmptyString('conf');

        $validator
            ->scalar('abr')
            ->maxLength('abr', 6)
            ->allowEmptyString('abr', null, function (array $context): bool {
                $post = !empty($context['data']['post']);
                $conf = !empty($context['data']['conf']);

                return !$post && !$conf;
            })
            ->add('abr', 'requiredWhenPostOrConf', [
                'rule' => function ($value, array $context): bool {
                    $post = !empty($context['data']['post']);
                    $conf = !empty($context['data']['conf']);
                    if (!$post && !$conf) {
                        return true;
                    }

                    return trim((string)$value) !== '';
                },
                'message' => 'Abbr is required when Post or Conf is set.',
            ]);

        return $validator;
    }
}
