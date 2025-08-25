<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SiteOptionsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('site_options');
        $this->setPrimaryKey('id');
        // Enable Timestamp behavior. Even though sqlite uses TEXT columns via migration,
        // Cake will still populate them with datetime strings; this avoids NOT NULL issues
        // in MySQL where defaults are not applied automatically when inserting without values.
        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('option_key')
            ->maxLength('option_key', 100)
            ->requirePresence('option_key', 'create')
            ->notEmptyString('option_key')
            ->add('option_key', 'unique', ['rule' => 'validateUnique', 'provider' => 'table'])
            ->allowEmptyString('value');

        return $validator;
    }
}
