<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class SeasonsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('seasons');
        $this->setPrimaryKey('id');
        $this->setDisplayField('start');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
    }
}
