<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * GameTypes Table
 *
 * @property \App\Model\Table\GamesTable $Games
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
