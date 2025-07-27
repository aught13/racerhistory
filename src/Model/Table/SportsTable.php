<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class SportsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('sports');
        $this->setPrimaryKey('id');
        $this->setDisplayField('sport_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
    }
}
