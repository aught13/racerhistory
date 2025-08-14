<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TeamsTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('teams');
        $this->setPrimaryKey('id');
        $this->setDisplayField('team_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
            'joinType' => 'INNER',
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
            ->integer('sport_id')
            ->requirePresence('sport_id', 'create')
            ->notEmptyString('sport_id');

        $validator
            ->scalar('team_name')
            ->maxLength('team_name', 162)
            ->requirePresence('team_name', 'create')
            ->notEmptyString('team_name');

        $validator
            ->scalar('team_description')
            ->maxLength('team_description', 240)
            ->allowEmptyString('team_description');

        $validator
            ->scalar('abbr')
            ->maxLength('abbr', 5)
            ->requirePresence('abbr', 'create')
            ->notEmptyString('abbr');

        $validator
            ->scalar('gender')
            ->maxLength('gender', 1)
            ->requirePresence('gender', 'create')
            ->notEmptyString('gender')
            ->inList('gender', ['M', 'F', 'C'], 'Gender must be M (Male), F (Female), or C (Co-ed)');

        return $validator;
    }
}
