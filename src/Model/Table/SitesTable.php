<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class SitesTable extends Table
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
        $this->setTable('sites');
        $this->setPrimaryKey('id');
        $this->setDisplayField('site_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Places', [
            'foreignKey' => 'place_id',
            'joinType' => 'INNER',
        ]);
    }
}
