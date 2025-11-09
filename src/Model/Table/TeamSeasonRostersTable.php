<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TeamSeasonRostersTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('team_season_roster');
        $this->setPrimaryKey('id');
        $this->belongsTo('TeamSeasons', [
            'foreignKey' => 'team_season_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Persons', [
            'foreignKey' => 'person_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('StatBasketGamePerson', [
            'foreignKey' => 'team_season_roster_id',
        ]);
        $this->hasMany('StatBasketSeasonPerson', [
            'foreignKey' => 'team_season_roster_id',
        ]);
        $this->setEntityClass('App\Model\Entity\TeamSeasonRosters');
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
            ->integer('team_season_id')
            ->requirePresence('team_season_id', 'create')
            ->notEmptyString('team_season_id');

        $validator
            ->integer('person_id')
            ->requirePresence('person_id', 'create')
            ->notEmptyString('person_id');

        $validator
            ->scalar('roster_year')
            ->maxLength('roster_year', 162)
            ->allowEmptyString('roster_year');

        $validator
            ->scalar('roster_number')
            ->maxLength('roster_number', 11)
            ->allowEmptyString('roster_number');

        $validator
            ->scalar('roster_position')
            ->maxLength('roster_position', 162)
            ->allowEmptyString('roster_position');

        $validator
            ->scalar('roster_height')
            ->maxLength('roster_height', 162)
            ->allowEmptyString('roster_height');

        $validator
            ->scalar('roster_weight')
            ->maxLength('roster_weight', 162)
            ->allowEmptyString('roster_weight');

        return $validator;
    }
}
