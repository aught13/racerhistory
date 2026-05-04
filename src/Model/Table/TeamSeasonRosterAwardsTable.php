<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * @property \App\Model\Table\TeamSeasonRostersTable&\Cake\ORM\Association\BelongsTo $TeamSeasonRosters
 * @method \App\Model\Entity\TeamSeasonRosterAward newEmptyEntity()
 * @method \App\Model\Entity\TeamSeasonRosterAward newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TeamSeasonRosterAward findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TeamSeasonRosterAward>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TeamSeasonRosterAward> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TeamSeasonRosterAward>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\TeamSeasonRosterAward[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TeamSeasonRosterAward> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TeamSeasonRosterAwardsTable extends Table
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
        $this->setTable('team_season_roster_awards');
        $this->setPrimaryKey('id');
        $this->belongsTo('TeamSeasonRosters', [
            'foreignKey' => 'team_season_roster_id',
            'joinType' => 'INNER',
        ]);
    }
}
