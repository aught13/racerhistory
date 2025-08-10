<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PersonsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Runtime configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('persons');
        $this->setPrimaryKey('id');
        $this->setDisplayField('display');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
    }
}
