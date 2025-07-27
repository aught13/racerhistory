<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class StatBasketSeasonOpponentTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('stat_basket_season_opponent');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeason', [
            'foreignKey' => 'team_season_id',
            'joinType' => 'LEFT',
        ]);
    }
}
