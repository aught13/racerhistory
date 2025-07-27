<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class GamesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('games');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeason', [
            'foreignKey' => 'team_season_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('GameTypes', [
            'foreignKey' => 'game_type_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Opponents', [
            'foreignKey' => 'opponent_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Places', [
            'foreignKey' => 'place_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Sites', [
            'foreignKey' => 'site_id',
            'joinType' => 'LEFT',
        ]);
    }
}
