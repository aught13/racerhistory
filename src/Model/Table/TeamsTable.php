<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TeamsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('teams');
        $this->setPrimaryKey('id');
        $this->setDisplayField('team_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
            'joinType' => 'INNER',
        ]);
    }
}
