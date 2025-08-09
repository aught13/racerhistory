<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SiteOptionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('site_options');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

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