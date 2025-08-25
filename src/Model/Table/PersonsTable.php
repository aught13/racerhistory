<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PersonsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Runtime configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('persons');
        $this->setPrimaryKey('id');
        $this->setDisplayField('display');
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
            ->allowEmptyString('first')
            ->allowEmptyString('last')
            ->allowEmptyString('full')
            ->requirePresence('display', 'create')
            ->notEmptyString('display', 'Display name is required')
            ->maxLength('first', 30)
            ->maxLength('last', 30)
            ->maxLength('full', 162)
            ->maxLength('display', 162)
            ->maxLength('person_image', 162)
            ->allowEmptyString('bio');

        return $validator;
    }
}
