<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class PlacesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('places');
        $this->setPrimaryKey('id');
        $this->setDisplayField('place_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
    }
}
