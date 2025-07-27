<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TeamSeasonTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('team_season');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Seasons', [
            'foreignKey' => 'season_id',
            'joinType' => 'INNER',
        ]);
    }
}
