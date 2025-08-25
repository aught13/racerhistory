<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class StatBasketGamePersonTable extends Table
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
