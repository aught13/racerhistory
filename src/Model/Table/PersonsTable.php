<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

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

        $this->hasMany('TeamSeasonRosters', [
            'foreignKey' => 'person_id',
        ]);

        // Add a callback to automatically set the full name
        $this->getEventManager()->on('Model.beforeSave', function ($event, $entity, $options): void {
            if ($entity instanceof \App\Model\Entity\Person) {
                if (empty($entity->full) && !empty($entity->first) && !empty($entity->last)) {
                    $entity->full = trim($entity->first . ' ' . $entity->last);
                }
            }
        });
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('first')
            ->allowEmptyString('last')
            ->allowEmptyString('full')
            ->requirePresence('display', 'create')
            ->notEmptyString('display', 'Display name is required')
            ->maxLength('first', 30)
            ->maxLength('last', 30)
            ->maxLength('full', 162)
            ->maxLength('display', 162)
            ->maxLength('person_image', 162)
            ->allowEmptyString('bio');

        return $validator;
    }
}
