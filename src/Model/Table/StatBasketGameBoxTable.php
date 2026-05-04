<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * @property \App\Model\Table\GamesTable&\Cake\ORM\Association\BelongsTo $Games
 * @property \App\Model\Table\OpponentsTable&\Cake\ORM\Association\BelongsTo $Opponents
 * @method \App\Model\Entity\StatBasketGameBox newEmptyEntity()
 * @method \App\Model\Entity\StatBasketGameBox newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatBasketGameBox findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameBox>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameBox> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameBox>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\StatBasketGameBox[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatBasketGameBox> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class StatBasketGameBoxTable extends Table
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
        $this->setTable('stat_basket_game_box');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Games', [
            'foreignKey' => 'game_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Opponents', [
            'foreignKey' => 'opponent_id',
            'joinType' => 'LEFT',
        ]);
    }
}
