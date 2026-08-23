<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PermissionsTable extends Table
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

        $this->setTable('permissions');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Build default validator for permission entities.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create')
            ->integer('role_id')
            ->notEmptyString('role_id')
            ->scalar('model_name')
            ->maxLength('model_name', 100)
            ->notEmptyString('model_name')
            ->boolean('can_create')
            ->notEmptyString('can_create')
            ->scalar('can_read')
            ->inList('can_read', ['none', 'own', 'all'])
            ->scalar('can_update')
            ->inList('can_update', ['none', 'own', 'all'])
            ->scalar('can_delete')
            ->inList('can_delete', ['none', 'own', 'all'])
            ->allowEmptyArray('custom_rules');

        return $validator;
    }

    /**
     * Build integrity rules for permissions table.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['role_id', 'model_name'], 'Role/model permission must be unique.'));
        $rules->add($rules->existsIn(['role_id'], 'Roles'));

        return $rules;
    }
}
