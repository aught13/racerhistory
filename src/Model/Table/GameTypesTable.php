<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class GameTypesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_types');
        $this->setPrimaryKey('id');
        $this->setDisplayField('game_type_name');
    }
}
