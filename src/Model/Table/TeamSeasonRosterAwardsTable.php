<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class TeamSeasonRosterAwardsTable extends Table
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
        $this->setTable('team_season_roster_awards');
        $this->setPrimaryKey('id');
        $this->belongsTo('TeamSeasonRoster', [
            'foreignKey' => 'team_season_roster_id',
            'joinType' => 'INNER',
        ]);
    }
}
