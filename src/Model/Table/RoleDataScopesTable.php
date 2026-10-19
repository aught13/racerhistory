<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Stores the team-owned data scope granted to each RBAC role.
 */
class RoleDataScopesTable extends Table
{
    /**
     * Initialize associations and table name.
     *
     * @param array<string,mixed> $config Runtime configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('role_data_scopes');
        $this->setPrimaryKey('id');
        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Build the role data-scope validator.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->integer('role_id')
            ->notEmptyString('role_id')
            ->scalar('scope_type')
            ->inList('scope_type', ['all', 'sport', 'team'])
            ->allowEmptyString('sport_key')
            ->maxLength('sport_key', 64)
            ->integer('team_id')
            ->allowEmptyString('team_id');
    }

    /**
     * Build role/team integrity rules.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['role_id'], 'Roles'));
        $rules->add($rules->existsIn(['team_id'], 'Teams'));

        return $rules;
    }
}
