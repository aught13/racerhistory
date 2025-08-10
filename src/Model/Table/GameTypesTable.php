<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class GameTypesTable extends Table
{
    /**
     * Initialize table configuration, behaviors and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_types');
        $this->setPrimaryKey('id');
        $this->setDisplayField('game_type_name');
    }
}
