<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Seasons Model
 *
 * Manages season data for organizing sports activities into time periods.
 * Seasons define the time periods during which teams compete.
 *
 * @method \App\Model\Entity\Season newEmptyEntity()
 * @method \App\Model\Entity\Season newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Season[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Season get($primaryKey, $options = [])
 * @method \App\Model\Entity\Season get($primaryKey, $contain = [])
 * @method \App\Model\Entity\Season findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Season patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Season[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Season|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Season saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Season[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Season[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Season[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Season[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * Table Fields:
 * - id: Primary key, auto-increment integer
 * - start: Starting year of the season (required)
 * - end: Ending year of the season (required)
 * - created_at: Timestamp when record was created
 * - updated_at: Timestamp when record was last modified
 */
class SeasonsTable extends Table
{
    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
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

        // Associations
        $this->hasMany('TeamSeasons', [
            'foreignKey' => 'season_id',
            'dependent' => true,
        ]);
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('start')
            ->maxLength('start', 255)
            ->requirePresence('start', 'create')
            ->notEmptyString('start');

        $validator
            ->scalar('end')
            ->maxLength('end', 255)
            ->requirePresence('end', 'create')
            ->notEmptyString('end');

        return $validator;
    }
}
