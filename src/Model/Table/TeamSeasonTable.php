<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TeamSeason Model
 *
 * Manages team season data that links teams to specific seasons with competition details.
 * Each team season represents a team's participation in a particular season.
 *
 * @method \App\Model\Entity\TeamSeason newEmptyEntity()
 * @method \App\Model\Entity\TeamSeason newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeason[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeason get($primaryKey, $options = [])
 * @method \App\Model\Entity\TeamSeason get($primaryKey, $contain = [])
 * @method \App\Model\Entity\TeamSeason findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TeamSeason patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeason[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TeamSeason|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TeamSeason saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TeamSeason[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamSeason[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamSeason[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TeamSeason[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * Table Fields:
 * - id: Primary key, auto-increment integer
 * - team_id: Foreign key to teams table (required)
 * - season_id: Foreign key to seasons table (required)
 * - semester: Semester number (required)
 * - league: League name (max 240 chars, optional)
 * - league_abbr: League abbreviation (max 10 chars, optional)
 * - league_finish: League finishing position (max 240 chars, optional)
 * - league_torunament_finish: League tournament finish (max 240 chars, optional)
 * - last_post_game: Last post game information (max 240 chars, optional)
 * - team_season_notes: Season notes (max 240 chars, optional)
 * - team_season_image: Season image filename (max 162 chars, optional)
 * - team_season_preview: Season preview text (optional)
 * - team_season_recap: Season recap text (optional)
 * - created_at: Timestamp when record was created
 * - updated_at: Timestamp when record was last modified
 */
class TeamSeasonTable extends Table
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
        $this->setTable('team_season');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Seasons', [
            'foreignKey' => 'season_id',
            'joinType' => 'INNER',
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
            ->integer('team_id')
            ->requirePresence('team_id', 'create')
            ->notEmptyString('team_id');

        $validator
            ->integer('season_id')
            ->requirePresence('season_id', 'create')
            ->notEmptyString('season_id');

        $validator
            ->integer('semester')
            ->requirePresence('semester', 'create')
            ->notEmptyString('semester');

        $validator
            ->scalar('league')
            ->maxLength('league', 240)
            ->allowEmptyString('league');

        $validator
            ->scalar('league_abbr')
            ->maxLength('league_abbr', 10)
            ->allowEmptyString('league_abbr');

        $validator
            ->scalar('league_finish')
            ->maxLength('league_finish', 240)
            ->allowEmptyString('league_finish');

        $validator
            ->scalar('league_torunament_finish')
            ->maxLength('league_torunament_finish', 240)
            ->allowEmptyString('league_torunament_finish');

        $validator
            ->scalar('last_post_game')
            ->maxLength('last_post_game', 240)
            ->allowEmptyString('last_post_game');

        $validator
            ->scalar('team_season_notes')
            ->maxLength('team_season_notes', 240)
            ->allowEmptyString('team_season_notes');

        $validator
            ->scalar('team_season_image')
            ->maxLength('team_season_image', 162)
            ->allowEmptyString('team_season_image');

        $validator
            ->scalar('team_season_preview')
            ->allowEmptyString('team_season_preview');

        $validator
            ->scalar('team_season_recap')
            ->allowEmptyString('team_season_recap');

        return $validator;
    }
}
