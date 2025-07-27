<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class StatBasketGamePersonTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('stat_basket_game_person');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeasonRoster', [
            'foreignKey' => 'team_season_roster_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Games', [
            'foreignKey' => 'game_id',
            'joinType' => 'LEFT',
        ]);
    }
}
