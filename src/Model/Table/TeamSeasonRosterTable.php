<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TeamSeasonRosterTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('team_season_roster');
        $this->setPrimaryKey('id');
        $this->belongsTo('TeamSeason', [
            'foreignKey' => 'team_season_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Persons', [
            'foreignKey' => 'person_id',
            'joinType' => 'INNER',
        ]);
    }
}
