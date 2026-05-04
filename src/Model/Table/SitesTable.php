<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * @property \App\Model\Table\PlacesTable&\Cake\ORM\Association\BelongsTo $Places
 * @method \App\Model\Entity\Site newEmptyEntity()
 * @method \App\Model\Entity\Site newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Site[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Site get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Site findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Site patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Site[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Site|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Site saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Site>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Site> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Site>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Site> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
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
