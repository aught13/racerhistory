<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class StatBasketGameBoxTable extends Table
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
        $this->setTable('stat_basket_game_box');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Games', [
            'foreignKey' => 'game_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Opponents', [
            'foreignKey' => 'opponent_id',
            'joinType' => 'LEFT',
        ]);
    }
}
