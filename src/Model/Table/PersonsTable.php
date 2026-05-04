<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Person;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\TeamSeasonRostersTable&\Cake\ORM\Association\HasMany $TeamSeasonRosters
 * @property \App\Model\Table\PlacesTable&\Cake\ORM\Association\BelongsTo $BirthPlace
 * @method \App\Model\Entity\Person newEmptyEntity()
 * @method \App\Model\Entity\Person newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Person[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Person get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Person findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Person patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Person[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Person|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Person saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Person[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Person[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Person[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Person[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
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

        $this->belongsTo('BirthPlace', [
            'className' => 'Places',
            'foreignKey' => 'birth_place_id',
            'joinType' => 'LEFT',
        ]);

        $this->setEntityClass('App\Model\Entity\Person');

        // Add a callback to automatically set the full name
        $this->getEventManager()->on('Model.beforeSave', function ($event, $entity, $options): void {
            if ($entity instanceof Person) {
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
            ->allowEmptyString('bio')
            ->integer('birth_place_id')
            ->allowEmptyString('birth_place_id')
            ->scalar('person_previous')
            ->maxLength('person_previous', 162)
            ->allowEmptyString('person_previous');

        return $validator;
    }
}
