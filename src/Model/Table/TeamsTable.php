<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Teams Model
 *
 * Manages team data for various sports in the application's historical sports information and statistics.
 *
 * @method \App\Model\Entity\Team newEmptyEntity()
 * @method \App\Model\Entity\Team newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Team[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Team get($primaryKey, array $options = [])
 * @method \App\Model\Entity\Team get($primaryKey, array $options = [])
 * @method \App\Model\Entity\Team get($primaryKey, ?array $options = null, mixed ...$args)
 * @method \App\Model\Entity\Team findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Team patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Team[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Team|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Team saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * Table Fields:
 * - id: Primary key, auto-increment integer
 * - sport_id: Foreign key to sports table (required)
 * - team_name: Short display name of the team (max 162 chars, required)
 * - team_description: Full official name including institution and sport (max 240 chars)
 * - abbr: Team abbreviation for display (max 5 chars, required)
 * - team_nickname: Team mascot or nickname (max 30 chars, required)
 * - team_scorebug: Shortened name for score display (max 6 chars, required)
 * - gender: Gender classification - M (Male), F (Female), C (Co-ed) (required)
 * - created_at: Timestamp when record was created
 * - updated_at: Timestamp when record was last modified
 */
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
            ->scalar('team_nickname')
            ->maxLength('team_nickname', 30)
            ->requirePresence('team_nickname', 'create')
            ->notEmptyString('team_nickname');

        $validator
            ->scalar('team_scorebug')
            ->maxLength('team_scorebug', 6)
            ->requirePresence('team_scorebug', 'create')
            ->notEmptyString('team_scorebug');

        $validator
            ->scalar('gender')
            ->maxLength('gender', 1)
            ->requirePresence('gender', 'create')
            ->notEmptyString('gender')
            ->inList('gender', ['M', 'F', 'C'], 'Gender must be M (Male), F (Female), or C (Co-ed)');

        return $validator;
    }
}
