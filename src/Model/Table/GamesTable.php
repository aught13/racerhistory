<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class GamesTable extends Table
{
    use LocatorAwareTrait;

    /**
     * Initialize table configuration and associations.
     *
     * @param array $config Runtime configuration for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('games');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
        $this->belongsTo('TeamSeason', [
            'foreignKey' => 'team_season_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('GameTypes', [
            'foreignKey' => 'game_type_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Opponents', [
            'foreignKey' => 'opponent_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Places', [
            'foreignKey' => 'place_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Sites', [
            'foreignKey' => 'site_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Validation callback to ensure cumulative scoring totals match EAV period/overtime sums.
     * Called as a validator rule for pts_mur so it runs during normal entity validation.
     *
     * @param mixed $value The pts_mur value
     * @param array $context Validation context, includes 'data' with submitted fields
     * @return bool True when validation passes, false to signal an error
     */
    public function validateCumulativeTotals(mixed $value, array $context): bool
    {
        // Only enforce when scoring_type for the sport is 'cumulative'.
        // We expect 'sport_id' to be present in the context data or available via the entity.
        $data = $context['data'] ?? [];

        $sportId = $data['sport_id'] ?? null;
        if (!$sportId && !empty($context['entity']) && is_object($context['entity'])) {
            $sportId = $context['entity']->get('sport_id') ?? null;
        }

        // If sport id still missing, try to derive from team_season_id
        if (!$sportId) {
            $teamSeasonId = $data['team_season_id'] ?? null;
            if (!$teamSeasonId && !empty($context['entity']) && is_object($context['entity'])) {
                $teamSeasonId = $context['entity']->get('team_season_id') ?? null;
            }
            if ($teamSeasonId) {
                try {
                    $ts = $this->fetchTable('TeamSeasons');
                    $teamSeason = $ts->get((int)$teamSeasonId, contain: ['Teams' => ['Sports']]);
                    if (!empty($teamSeason->team) && !empty($teamSeason->team->sport)) {
                        $sportId = $teamSeason->team->sport->id;
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        $sportResolved = (bool)$sportId;
        $scoringType = null;
        if ($sportResolved) {
            try {
                /** @var \App\Model\Table\SportConfigsTable $sportConfigs */
                $sportConfigs = $this->getTableLocator()->get('SportConfigs');
                $configs = $sportConfigs->getConfigsForSport((int)$sportId);
                $scoringType = $configs['scoring_type'] ?? null;
                if ($scoringType !== 'cumulative') {
                    return true;
                }
            } catch (\Throwable $e) {
                // If the config lookup fails, fall through and validate if per-period data present
            }
        }

        // Extract totals. pts_mur is provided as $value; pts_opp may be in data or entity
        $totalTeam = (int)$value;
        if (isset($data['pts_opp'])) {
            $totalOpp = (int)$data['pts_opp'];
        } elseif (!empty($context['entity']) && is_object($context['entity'])) {
            $totalOpp = (int)$context['entity']->get('pts_opp');
        } else {
            $totalOpp = 0;
        }

        // Gather period and overtime keys from submitted data and legacy keys
        $periodTeamSum = 0;
        $periodOppSum = 0;

        foreach ($data as $k => $v) {
            if (!is_scalar($v) || $v === '') {
                continue;
            }
            if (preg_match('/^period_\d+_(?:team|mur)$/', $k)) {
                $periodTeamSum += (int)$v;
                continue;
            }
            if (preg_match('/^period_\d+_(?:opponent|opp)$/', $k)) {
                $periodOppSum += (int)$v;
                continue;
            }
            if (preg_match('/^overtime_\d+_\d+_(?:team|mur)$/', $k)) {
                $periodTeamSum += (int)$v;
                continue;
            }
            if (preg_match('/^overtime_\d+_\d+_(?:opponent|opp)$/', $k)) {
                $periodOppSum += (int)$v;
                continue;
            }
            if (preg_match('/^period_\d+_mur$/', $k)) {
                $periodTeamSum += (int)$v;
                continue;
            }
            if (preg_match('/^period_\d+_opp$/', $k)) {
                $periodOppSum += (int)$v;
                continue;
            }
        }

        // If no per-period data was submitted, skip validation here (nothing to compare)
        if ($periodTeamSum === 0 && $periodOppSum === 0) {
            return true;
        }

        // Only enforce comparison for a side when a total was provided (> 0).
        $teamMismatch = ($totalTeam > 0) && ($periodTeamSum !== $totalTeam);
        $oppMismatch = ($totalOpp > 0) && ($periodOppSum !== $totalOpp);

        if ($teamMismatch || $oppMismatch) {
            return false;
        }

        return true;
    }

    /**
     * Application rules for checking integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules checker to configure
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // Ensure the referenced TeamSeason exists
        $rules->add($rules->existsIn(['team_season_id'], 'TeamSeason'), ['errorField' => 'team_season_id']);

        return $rules;
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
            ->integer('team_season_id')
            ->requirePresence('team_season_id', 'create')
            ->notEmptyString('team_season_id');

        $validator
            ->date('game_date')
            ->allowEmptyDate('game_date')
            ->add('game_date', 'futureLimit', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return true;
                    }
                    try {
                        $dt = new \DateTime((string)$value);
                    } catch (\Exception $e) {
                        return false;
                    }
                    $now = new \DateTime('today');
                    // Allow dates up to 3 years in the future
                    $max = (clone $now)->modify('+3 years');

                    return $dt <= $max;
                },
                'message' => 'Game date cannot be more than 3 years in the future.',
            ]);

        // Ensure game_date falls within the season years for the selected team_season_id
        $validator->add('game_date', 'withinSeason', [
            'rule' => function ($value, $context) {
                $data = $context['data'] ?? [];
                $teamSeasonId = $data['team_season_id'] ?? null;
                if (!$teamSeasonId || !$value) {
                    return true;
                }
                try {
                    $tsTable = $this->fetchTable('TeamSeasons');
                    $teamSeason = $tsTable->find()
                        ->contain(['Seasons'])
                        ->where(['TeamSeasons.id' => (int)$teamSeasonId])
                        ->first();
                    if (!$teamSeason || !$teamSeason->season) {
                        return true; // let existsIn handle missing relations
                    }
                    $year = (int)(new \DateTime((string)$value))->format('Y');
                    $start = (int)$teamSeason->season->start;
                    $end = (int)$teamSeason->season->end;

                    return $year >= $start && $year <= $end;
                } catch (\Exception $e) {
                    return true;
                }
            },
            'message' => 'Game date must fall within the season years for the selected team season.',
        ]);

        // If scoring_type is cumulative, ensure period sums match totals on the entity data
        $validator->add('pts_mur', 'cumulativeTotals', [
            'rule' => [$this, 'validateCumulativeTotals'],
            'message' => 'Sum of period/overtime scores must equal the reported total when scoring type is cumulative.',
        ]);

        $validator
            ->scalar('game_time')
            ->maxLength('game_time', 10)
            ->allowEmptyString('game_time');

        $validator
            ->scalar('game_duration')
            ->maxLength('game_duration', 20)
            ->allowEmptyString('game_duration');

        $validator
            ->integer('game_type_id')
            ->allowEmptyString('game_type_id');

        $validator
            ->integer('opponent_id')
            ->allowEmptyString('opponent_id');

        $validator
            ->integer('place_id')
            ->allowEmptyString('place_id');

        $validator
            ->integer('site_id')
            ->allowEmptyString('site_id');

        $validator
            ->integer('hrn')
            ->inList('hrn', [1, 2, 3], 'Location must be Home (1), Road (2), or Neutral (3).')
            ->allowEmptyString('hrn');

        $validator
            ->boolean('post')
            ->allowEmptyString('post');

        $validator
            ->integer('pts_mur')
            ->allowEmptyString('pts_mur');

        $validator
            ->integer('pts_opp')
            ->allowEmptyString('pts_opp');

        // If the game is scheduled in the future, prevent scores or W/L flags
        $validator->add('pts_mur', 'futureNoScore', [
            'rule' => function ($value, $context) {
                $data = $context['data'] ?? [];
                if (empty($data['game_date'])) {
                    return true;
                }
                try {
                    $gameDate = new \DateTime((string)$data['game_date']);
                } catch (\Exception $e) {
                    return true;
                }
                $today = new \DateTime('today');
                if ($gameDate > $today) {
                    // Future game: no scores should be present
                    return empty($value) && empty($data['pts_opp']) && empty($data['w']) && empty($data['l']);
                }

                return true; // Past or today - allow scores
            },
            'message' => 'Future games must not have scores or a result set.',
        ]);

        // Sport-specific validation
        $validator->add('periods', 'sportSpecificPeriods', [
            'rule' => [$this, 'validateSportSpecificPeriods'],
            'message' => 'Period count must be valid for this sport.',
        ]);

        return $validator;
    }

    /**
     * Validate periods count based on sport configuration.
     *
     * @param mixed $value The field value
     * @param array $context Context containing entity data
     * @return bool
     */
    public function validateSportSpecificPeriods(mixed $value, array $context): bool
    {
        if (empty($value) || empty($context['data']['team_season_id'])) {
            return true; // Skip validation if no periods or team season
        }

        $teamSeasonId = (int)$context['data']['team_season_id'];

        try {
            // Get sport from team season
            /** @var \App\Model\Table\TeamSeasonsTable $teamSeasonsTable */
            $teamSeasonsTable = $this->fetchTable('TeamSeasons');
            $teamSeason = $teamSeasonsTable->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $teamSeasonId])
                ->first();

            if (!$teamSeason->team || !$teamSeason->team->sport) {
                return true; // Allow if sport not found
            }

            $sportId = $teamSeason->team->sport->id;

            // Get sport configuration
            /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            $configs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

            // Check if periods value is valid for this sport
            $periodsInt = (int)$value;
            // Default valid periods fallback (e.g., basketball)
            $validPeriods = $configs['valid_periods'] ?? [2, 4];

            return in_array($periodsInt, $validPeriods, true);
        } catch (\Exception $e) {
            return true; // Allow if validation fails
        }
    }

    /**
     * Validate EAV data against sport configuration.
     *
     * @param array $eavData EAV data to validate
     * @param int $teamSeasonId Team season ID
     * @return array Validation errors
     */
    public function validateEavData(array $eavData, int $teamSeasonId): array
    {
        $errors = [];

        try {
            // Get sport from team season
            /** @var \App\Model\Table\TeamSeasonsTable $teamSeasonsTable */
            $teamSeasonsTable = $this->fetchTable('TeamSeasons');
            $teamSeason = $teamSeasonsTable->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $teamSeasonId])
                ->first();

            if (!$teamSeason->team || !$teamSeason->team->sport) {
                return $errors; // No validation if sport not found
            }

            $sportId = $teamSeason->team->sport->id;

            // Get sport configuration and EAV template
            /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            // Use the simpler key=>value API so tests and validators read raw config values
            $configs = $sportConfigsTable->getConfigsForSport((int)$sportId);

            /** @var \App\Model\Table\GameEavTable $gameEavTable */
            $gameEavTable = $this->fetchTable('GameEav');
            $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId);

            // Validate officials count
            $officialsConfig = $configs['officials'] ?? ['Official 1', 'Official 2'];
            $expectedOfficials = count($officialsConfig);
            $actualOfficials = 0;

            for ($i = 1; $i <= $expectedOfficials; $i++) {
                if (!empty($eavData["official_{$i}"])) {
                    $actualOfficials++;
                }
            }

            // Warn if not enough officials (but don't block)
            if ($actualOfficials < $expectedOfficials) {
                // Use sprintf to keep line length within coding standard limits
                $errors['officials'] = sprintf(
                    'This sport typically requires %d officials, but only %d were provided.',
                    $expectedOfficials,
                    $actualOfficials
                );
            }

            // Validate period scores are non-negative integers
            foreach ($eavTemplate as $fieldName => $fieldConfig) {
                if (strpos($fieldName, 'period_') === 0 && strpos($fieldName, '_') !== false) {
                    $value = $eavData[$fieldName] ?? null;
                    if ($value !== null && $value !== '') {
                        if (!is_numeric($value) || (int)$value < 0) {
                            $errors[$fieldName] = 'Period scores must be non-negative numbers.';
                        }
                    }
                }
            }

            // If scoring_type is cumulative, ensure the sum of period and overtime fields equals totals
            $scoringType = $configs['scoring_type'] ?? 'cumulative';
            if ($scoringType === 'cumulative') {
                // Build sum for team and opponent from EAV template fields
                $periodSumTeam = 0;
                $periodSumOpp = 0;
                foreach ($eavTemplate as $fieldName => $fieldConfig) {
                    if (preg_match('/^period_(\d+)_team$/', $fieldName)) {
                        $periodSumTeam += (int)($eavData[$fieldName] ?? 0);
                    }
                    if (preg_match('/^period_(\d+)_opponent$/', $fieldName)) {
                        $periodSumOpp += (int)($eavData[$fieldName] ?? 0);
                    }
                    if (preg_match('/^overtime_(\d+)_team$/', $fieldName)) {
                        $periodSumTeam += (int)($eavData[$fieldName] ?? 0);
                    }
                    if (preg_match('/^overtime_(\d+)_opponent$/', $fieldName)) {
                        $periodSumOpp += (int)($eavData[$fieldName] ?? 0);
                    }
                }

                // Compare against pts_mur / pts_opp in provided EAV or surrounding data
                // Note: caller should pass in pts_mur/pts_opp in $eavData when available
                $totalTeam = (int)($eavData['pts_mur'] ?? 0);
                $totalOpp = (int)($eavData['pts_opp'] ?? 0);

                if ($totalTeam > 0 && $periodSumTeam !== $totalTeam) {
                    $errors['periods_team_total'] = 'Sum of period scores does not equal team total (pts_mur).';
                }
                if ($totalOpp > 0 && $periodSumOpp !== $totalOpp) {
                    $errors['periods_opp_total'] = 'Sum of period scores does not equal opponent total (pts_opp).';
                }
            }
        } catch (\Exception $e) {
            // Validation failed - don't block save but log warning
            $errors['validation'] = 'Could not validate against sport configuration.';
        }

        return $errors;
    }
}
