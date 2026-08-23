<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class RolesTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array<string, mixed> $config Configuration values.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');
        $this->addBehavior('Timestamp');

        $this->hasMany('Permissions', [
            'foreignKey' => 'role_id',
            'dependent' => true,
        ]);
        $this->hasMany('Users', [
            'foreignKey' => 'role_id',
        ]);
    }

    /**
     * Build default validator for role entities.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create')
            ->scalar('name')
            ->maxLength('name', 100)
            ->notEmptyString('name');

        return $validator;
    }

    /**
     * Build integrity rules for roles table.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['name'], 'Role name must be unique.'));

        return $rules;
    }
}
