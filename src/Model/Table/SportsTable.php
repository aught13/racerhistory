<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SportsTable extends Table
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
        $this->setTable('sports');
        $this->setPrimaryKey('id');
        $this->setDisplayField('sport_name');
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
