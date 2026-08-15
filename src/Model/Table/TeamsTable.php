<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Service\SportConfigService;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Teams Model
 *
 * Manages team data for various sports in the application's historical sports information and statistics.
 *
 * @method \App\Model\Entity\Team newEmptyEntity()
 * @method \App\Model\Entity\Team newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Team[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Team get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Team get($primaryKey, array $options = [])
 * @method \App\Model\Entity\Team get($primaryKey, ?array $options = null, mixed ...$args)
 * @method \App\Model\Entity\Team findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Team patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Team[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Team|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Team saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * Table Fields:
 * - id: Primary key, auto-increment integer
 * - sport_id: Legacy numeric sport reference (transitional)
 * - sport_key: Canonical sport key from configured defaults
 * - team_name: Short display name of the team (max 162 chars, required)
 * - team_description: Full official name including institution and sport (max 240 chars)
 * - abbr: Team abbreviation for display (max 5 chars, required)
 * - team_nickname: Team mascot or nickname (max 30 chars, required)
 * - team_scorebug: Shortened name for score display (max 6 chars, required)
 * - gender: Gender classification - M (Male), F (Female), C (Co-ed) (required)
 * - created_at: Timestamp when record was created
 * - updated_at: Timestamp when record was last modified
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class TeamsTable extends Table
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
        $this->setTable('teams');
        $this->setPrimaryKey('id');
        $this->setDisplayField('team_name');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
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
        $hasSportIdColumn = $this->getSchema()->hasColumn('sport_id');
        $hasSportKeyColumn = $this->getSchema()->hasColumn('sport_key');

        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        if ($hasSportIdColumn) {
            $validator
                ->integer('sport_id')
                ->allowEmptyString('sport_id');

            if (!$hasSportKeyColumn) {
                $validator
                    ->requirePresence('sport_id', 'create')
                    ->notEmptyString('sport_id');
            }
        }

        if ($hasSportKeyColumn) {
            $validator
                ->scalar('sport_key')
                ->maxLength('sport_key', 64)
                ->requirePresence('sport_key', 'create')
                ->notEmptyString('sport_key');
        } else {
            $validator
                ->scalar('sport_key')
                ->maxLength('sport_key', 64)
                ->allowEmptyString('sport_key');
        }

        $validator
            ->scalar('team_name')
            ->maxLength('team_name', 162)
            ->requirePresence('team_name', 'create')
            ->notEmptyString('team_name');

        $validator
            ->scalar('team_description')
            ->maxLength('team_description', 240)
            ->allowEmptyString('team_description');

        $validator
            ->scalar('abbr')
            ->maxLength('abbr', 5)
            ->requirePresence('abbr', 'create')
            ->notEmptyString('abbr');

        $validator
            ->scalar('team_nickname')
            ->maxLength('team_nickname', 30)
            ->requirePresence('team_nickname', 'create')
            ->notEmptyString('team_nickname');

        $validator
            ->scalar('team_scorebug')
            ->maxLength('team_scorebug', 6)
            ->requirePresence('team_scorebug', 'create')
            ->notEmptyString('team_scorebug');

        $validator
            ->scalar('gender')
            ->maxLength('gender', 1)
            ->requirePresence('gender', 'create')
            ->notEmptyString('gender')
            ->inList('gender', ['M', 'F', 'C'], 'Gender must be M (Male), F (Female), or C (Co-ed)');

        return $validator;
    }

    /**
     * Keep sport_id and sport_key synchronized while both columns coexist.
     *
     * @param \Cake\Event\EventInterface<\Cake\Datasource\EntityInterface> $event Event object
     * @param \Cake\Datasource\EntityInterface $entity Pending team entity
     * @param \ArrayObject<string,mixed> $options Save options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $sportConfigService = new SportConfigService();
        $hasSportKeyColumn = $this->getSchema()->hasColumn('sport_key');
        $hasSportIdColumn = $this->getSchema()->hasColumn('sport_id');

        if (!$hasSportKeyColumn && !$hasSportIdColumn) {
            return;
        }

        $sportKey = trim((string)($entity->get('sport_key') ?? ''));
        $sportId = $hasSportIdColumn ? (int)($entity->get('sport_id') ?? 0) : 0;

        if ($hasSportKeyColumn && $sportKey === '' && $hasSportIdColumn && $sportId > 0) {
            $resolvedKey = $sportConfigService->getKeyById($sportId);
            if ($resolvedKey !== null) {
                $entity->set('sport_key', $resolvedKey);
            }
        }

        if ($hasSportIdColumn && $sportKey !== '' && $sportId <= 0) {
            $resolvedId = $sportConfigService->getIdByKey($sportKey);
            if ($resolvedId !== null) {
                $entity->set('sport_id', $resolvedId);
            }
        }

        if (!$hasSportKeyColumn && $entity->isDirty('sport_key')) {
            $entity->unset('sport_key');
        }

        if (!$hasSportIdColumn && $entity->isDirty('sport_id')) {
            $entity->unset('sport_id');
        }
    }
}
