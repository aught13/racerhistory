<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @method \App\Model\Entity\SiteOption newEmptyEntity()
 * @method \App\Model\Entity\SiteOption newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SiteOption[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SiteOption get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SiteOption findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SiteOption patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SiteOption[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SiteOption|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SiteOption saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SiteOption[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiteOption>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SiteOption[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiteOption> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SiteOption[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiteOption>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SiteOption[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiteOption> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class SiteOptionsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array $config Config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('site_options');
        $this->setPrimaryKey('id');
        // Enable Timestamp behavior. Even though sqlite uses TEXT columns via migration,
        // Cake will still populate them with datetime strings; this avoids NOT NULL issues
        // in MySQL where defaults are not applied automatically when inserting without values.
        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('option_key')
            ->maxLength('option_key', 100)
            ->requirePresence('option_key', 'create')
            ->notEmptyString('option_key')
            ->add('option_key', 'unique', ['rule' => 'validateUnique', 'provider' => 'table'])
            ->allowEmptyString('value');

        return $validator;
    }
}
