<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\SportConfig;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SportConfigs Model
 *
 * @property \App\Model\Table\SportsTable&\Cake\ORM\Association\BelongsTo $Sports
 * @method \App\Model\Entity\SportConfig newEmptyEntity()
 * @method \App\Model\Entity\SportConfig newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SportConfig[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SportConfig get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SportConfig findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SportConfig patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SportConfig[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SportConfig|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SportConfig saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SportConfig[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportConfig>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SportConfig[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportConfig> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SportConfig[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportConfig>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\SportConfig[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SportConfig> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class SportConfigsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sport_configs');
        $this->setDisplayField('config_key');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Sports', [
            'foreignKey' => 'sport_id',
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
            ->integer('sport_id')
            ->notEmptyString('sport_id');

        $validator
            ->scalar('config_key')
            ->maxLength('config_key', 50)
            ->requirePresence('config_key', 'create')
            ->notEmptyString('config_key');

        $validator
            ->scalar('config_value')
            ->allowEmptyString('config_value');

        $validator
            ->scalar('description')
            ->maxLength('description', 200)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['sport_id'], 'Sports'), ['errorField' => 'sport_id']);
        $rules->add($rules->isUnique(['sport_id', 'config_key']), ['errorField' => 'config_key']);

        return $rules;
    }

    /**
     * Get all configurations for a sport as key-value pairs
     *
     * @param int $sportId Sport ID
     * @return array<string, mixed> Configuration array
     */
    public function getConfigsForSport(int $sportId): array
    {
        $configs = $this->find()
            ->select(['config_key', 'config_value'])
            ->where(['sport_id' => $sportId])
            ->toArray();

        $result = [];
        foreach ($configs as $config) {
            if (!($config instanceof SportConfig)) {
                continue;
            }
            $value = $config->config_value;
            // Try to decode JSON values
            $decoded = json_decode($value, true);
            $result[$config->config_key] = $decoded ?? $value;
        }

        return $result;
    }

    /**
     * Set or update a configuration value for a sport
     *
     * @param int $sportId Sport ID
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param string|null $description Optional description
     * @return \App\Model\Entity\SportConfig|false
     */
    public function setConfig(int $sportId, string $key, mixed $value, ?string $description = null): mixed
    {
        // Find existing config or create new
        $config = $this->find()
            ->where(['sport_id' => $sportId, 'config_key' => $key])
            ->first();

        if (!($config instanceof SportConfig)) {
            $config = $this->newEmptyEntity();
            $config->sport_id = $sportId;
            $config->config_key = $key;
        }

        // Handle array/object values by JSON encoding
        $config->config_value = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        if ($description !== null) {
            $config->description = $description;
        }

        return $this->save($config);
    }

    /**
     * Get sport configuration with user-friendly formatting
     *
     * @param int $sportId Sport ID
     * @return array<string, mixed> Formatted configuration
     */
    public function getFormattedConfigsForSport(int $sportId): array
    {
        $configs = $this->find()
            ->select(['config_key', 'config_value', 'description'])
            ->where(['sport_id' => $sportId])
            ->orderBy(['config_key'])
            ->toArray();

        $formatted = [
            'period_names' => [],
            'officials' => [],
            'settings' => [],
        ];

        foreach ($configs as $config) {
            if (!($config instanceof SportConfig)) {
                continue;
            }
            $key = $config->config_key;
            $value = $config->config_value;
            $decoded = json_decode($value, true);
            $actualValue = $decoded ?? $value;

            // Categorize configs for better display
            if (str_starts_with($key, 'period_name_')) {
                $periods = str_replace('period_name_', '', $key);
                $formatted['period_names'][$periods] = [
                    'value' => $actualValue,
                    'description' => $config->description,
                ];
            } elseif ($key === 'officials') {
                $formatted['officials'] = [
                    'value' => $actualValue,
                    'description' => $config->description,
                ];
            } else {
                $formatted['settings'][$key] = [
                    'value' => $actualValue,
                    'description' => $config->description,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Save bulk configurations for a sport
     *
     * @param int $sportId Sport ID
     * @param array<string, mixed> $configs Configuration array
     * @return bool Success status
     */
    public function saveBulkConfigs(int $sportId, array $configs): bool
    {
        // First, delete all existing configs for this sport to ensure clean slate
        $this->deleteAll(['sport_id' => $sportId]);

        $success = true;

        foreach ($configs as $key => $data) {
            // Handle temporary period name keys from JavaScript form
            if (
                str_starts_with($key, 'period_name_new_')
                && is_array($data)
                && isset($data['periods'], $data['value'])
            ) {
                // Convert period_name_new_X to period_name_Y where Y is the periods value
                $periods = $data['periods'];
                $actualKey = "period_name_{$periods}";
                $value = $data['value'];
                $description = $data['description'] ?? null;
            } elseif (is_array($data) && isset($data['value'])) {
                $actualKey = $key;
                $value = $data['value'];
                $description = $data['description'] ?? null;
            } else {
                $actualKey = $key;
                $value = $data;
                $description = null;
            }

            $result = $this->setConfig($sportId, $actualKey, $value, $description);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get default configuration template for a new sport
     *
     * @return array<string, mixed> Default configuration template
     */
    public function getDefaultConfigTemplate(): array
    {
        return [
            'period_name_2' => [
                'value' => 'Half',
                'description' => 'Period name for 2-period games',
            ],
            'period_name_4' => [
                'value' => 'Quarter',
                'description' => 'Period name for 4-period games',
            ],
            'overtime_name' => [
                'value' => 'OT',
                'description' => 'Name for overtime periods',
            ],
            'default_periods' => [
                'value' => '2',
                'description' => 'Default number of periods for this sport',
            ],
            'supports_periods' => [
                'value' => [2, 4],
                'description' => 'Array of supported period counts',
            ],
            'officials' => [
                'value' => ['Referee 1', 'Referee 2', 'Official 1'],
                'description' => 'Array of official titles',
            ],
            'scoring_type' => [
                'value' => 'cumulative',
                'description' => 'How scoring works: cumulative or by_period',
            ],
        ];
    }
}
