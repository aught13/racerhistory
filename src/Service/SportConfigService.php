<?php
declare(strict_types=1);

namespace App\Service;

use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * Sport configuration service.
 *
 * Source of truth:
 * - Static defaults from Configure::read('SportsDefaults')
 * - Optional per-sport runtime overrides stored in SiteOptions as JSON
 */
class SportConfigService
{
    use LocatorAwareTrait;
    use ServiceAwareTrait;

    private const OVERRIDE_OPTION_PREFIX = 'sports.override.';
    private const MERGED_CACHE_KEY_PREFIX = 'sports_config_merged_';
    private const FALLBACK_SPORT_KEY = 'basketball';
    private const ANY_PERIODS_VALUE = 'any';

    /**
     * Derived stat table naming uses short slugs for legacy compatibility.
     *
     * @var array<string,string>
     */
    private const SPORT_TABLE_SLUGS = [
        'basketball' => 'basket',
        'football' => 'football',
        'baseball' => 'baseball',
    ];

    /**
     * @var array<string,array<int,string>>
     */
    private const STAT_TABLE_CONTEXTS = [
        'game' => ['team', 'opponent', 'player', 'box'],
        'season' => ['team', 'opponent', 'player'],
    ];

    /**
     * @var array<string,string>
     */
    private const DEFAULT_FIELD_LABELS = [
        'MIN' => 'Minutes',
        'FGM' => 'Field Goals Made',
        'FGA' => 'Field Goals Attempted',
        '3PM' => '3-Point Field Goals Made',
        '3PA' => '3-Point Field Goals Attempted',
        'FTM' => 'Free Throws Made',
        'FTA' => 'Free Throws Attempted',
        'OREB' => 'Offensive Rebounds',
        'ORB' => 'Offensive Rebounds',
        'DREB' => 'Defensive Rebounds',
        'REB' => 'Total Rebounds',
        'AST' => 'Assists',
        'STL' => 'Steals',
        'BLK' => 'Blocks',
        'TO' => 'Turnovers',
        'PF' => 'Personal Fouls',
        'PTS' => 'Points',
        'PNT' => 'Points in Paint',
        'OTO' => 'Points Off Turnovers',
        'SND' => 'Second-Chance Points',
        'FB' => 'Fast Break Points',
        'BN' => 'Bench Points',
        'TIED' => 'Times Tied',
        'LC' => 'Lead Changes',
        'COMP' => 'Completions',
        'ATT' => 'Attempts',
        'YDS' => 'Passing Yards',
        'TD' => 'Passing Touchdowns',
        'INT' => 'Interceptions Thrown',
        'RUSH' => 'Rushing Attempts',
        'RYDS' => 'Rushing Yards',
        'RTD' => 'Rushing Touchdowns',
        'REC' => 'Receptions',
        'RECYDS' => 'Receiving Yards',
        'RECTD' => 'Receiving Touchdowns',
        'TKL' => 'Tackles',
        'SACK' => 'Sacks',
        'FF' => 'Forced Fumbles',
        'FR' => 'Fumble Recoveries',
        'DINT' => 'Interceptions',
        'PD' => 'Passes Defended',
        'DTD' => 'Defensive Touchdowns',
        'XPM' => 'Extra Points Made',
        'XPA' => 'Extra Points Attempted',
        'PUNTS' => 'Punts',
        'PAVG' => 'Punting Average',
        'TB' => 'Touchbacks',
        'I20' => 'Inside 20',
    ];

    /**
     * @var array<string,array<string,array<string,mixed>>>
     */
    private const DEFAULT_CALCULATED_FIELDS_BY_SPORT = [
        'basketball' => [
            'FG%' => [
                'formula' => 'FGM / FGA * 100',
                'condition' => 'FGA > 0',
                'format' => '%.1f%%',
            ],
            '3P%' => [
                'formula' => '3PM / 3PA * 100',
                'condition' => '3PA > 0',
                'format' => '%.1f%%',
            ],
            'FT%' => [
                'formula' => 'FTM / FTA * 100',
                'condition' => 'FTA > 0',
                'format' => '%.1f%%',
            ],
        ],
        'football' => [
            'COMP%' => [
                'formula' => 'COMP / ATT * 100',
                'condition' => 'ATT > 0',
                'format' => '%.1f%%',
            ],
            'YPA' => [
                'formula' => 'YDS / ATT',
                'condition' => 'ATT > 0',
                'format' => '%.1f',
            ],
            'YPR' => [
                'formula' => 'RYDS / RUSH',
                'condition' => 'RUSH > 0',
                'format' => '%.1f',
            ],
            'YPREC' => [
                'formula' => 'RECYDS / REC',
                'condition' => 'REC > 0',
                'format' => '%.1f',
            ],
        ],
    ];

    private string $cacheConfig = 'default';

    private SiteOptionService $siteOptionService;

    /**
     * @param \App\Service\SiteOptionService|null $siteOptionService
     */
    public function __construct(?SiteOptionService $siteOptionService = null)
    {
        $this->siteOptionService = $siteOptionService ?? new SiteOptionService();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getAllSportsDefaults(): array
    {
        $configured = Configure::read('SportsDefaults');
        if (!is_array($configured)) {
            return $this->fallbackDefaults();
        }

        $defaults = [];
        foreach ($configured as $sportKey => $sportConfig) {
            if (!is_string($sportKey) || !is_array($sportConfig)) {
                continue;
            }

            $normalizedKey = $this->normalizeSportKey($sportKey);
            if ($normalizedKey === '') {
                continue;
            }

            $displayName = isset($sportConfig['name'])
                ? (string)$sportConfig['name']
                : $this->humanizeKey($normalizedKey);
            $defaults[$normalizedKey] = $this->stripLegacyAliases(
                $this->normalizeConfig($sportConfig, $normalizedKey, $displayName),
            );
        }

        if ($defaults === []) {
            return $this->fallbackDefaults();
        }

        return $defaults;
    }

    /**
     * @return array<string,string>
     */
    public function getAvailableSports(): array
    {
        $options = [];
        foreach ($this->getAllSportsDefaults() as $sportKey => $config) {
            $options[$sportKey] = (string)($config['name'] ?? $this->humanizeKey($sportKey));
        }

        return $options;
    }

    /**
     * @return string
     */
    public function getDefaultSportKey(): string
    {
        $defaults = $this->getAllSportsDefaults();
        if ($defaults === []) {
            return self::FALLBACK_SPORT_KEY;
        }

        if (isset($defaults[self::FALLBACK_SPORT_KEY])) {
            return self::FALLBACK_SPORT_KEY;
        }

        return (string)array_key_first($defaults);
    }

    /**
     * @param string $sportKey
     * @return string
     */
    public function getSportDisplayName(string $sportKey): string
    {
        $normalizedKey = $this->normalizeSportKey($sportKey);
        $defaults = $this->getAllSportsDefaults();

        if ($normalizedKey !== '' && isset($defaults[$normalizedKey]['name'])) {
            return (string)$defaults[$normalizedKey]['name'];
        }

        return $this->humanizeKey($normalizedKey !== '' ? $normalizedKey : $sportKey);
    }

    /**
     * Canonical merged config for a sport key.
     *
     * @param string $sportKey
     * @return array<string,mixed>
     */
    public function getMergedConfig(string $sportKey): array
    {
        $normalizedKey = $this->normalizeSportKey($sportKey);
        if ($normalizedKey === '') {
            return [];
        }

        $cacheKey = self::MERGED_CACHE_KEY_PREFIX . $normalizedKey;
        $cached = Cache::read($cacheKey, $this->cacheConfig);
        if (is_array($cached)) {
            return $cached;
        }

        $defaults = $this->getAllSportsDefaults();
        if (!isset($defaults[$normalizedKey])) {
            return [];
        }

        $baseConfig = $defaults[$normalizedKey];
        $overrideConfig = $this->getOverrideConfig($normalizedKey);

        $mergedCanonical = $this->mergeConfigRecursive($baseConfig, $overrideConfig);
        $merged = $this->normalizeConfig(
            $mergedCanonical,
            $normalizedKey,
            (string)($baseConfig['name'] ?? $this->humanizeKey($normalizedKey)),
        );

        Cache::write($cacheKey, $merged, $this->cacheConfig);

        return $merged;
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getMergedConfigById(int $sportId): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [];
        }

        return $this->getMergedConfig($sportKey);
    }

    /**
     * @param string $sportKey
     * @return array<string,mixed>
     */
    public function getDefaultConfig(string $sportKey): array
    {
        $normalizedKey = $this->normalizeSportKey($sportKey);
        $defaults = $this->getAllSportsDefaults();

        return $defaults[$normalizedKey] ?? [];
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getDefaultConfigById(int $sportId): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [];
        }

        return $this->getDefaultConfig($sportKey);
    }

    /**
     * @param string $sportKey
     * @param array<string,mixed> $mergedConfig
     */
    public function saveMergedConfig(string $sportKey, array $mergedConfig): bool
    {
        $normalizedKey = $this->normalizeSportKey($sportKey);
        if ($normalizedKey === '') {
            return false;
        }

        $defaults = $this->getDefaultConfig($normalizedKey);
        if ($defaults === []) {
            return false;
        }

        $normalizedMerged = $this->stripLegacyAliases(
            $this->normalizeConfig($mergedConfig, $normalizedKey, $this->getSportDisplayName($normalizedKey)),
        );

        $overrideResult = $this->diffConfigRecursive($defaults, $normalizedMerged);
        $override = $overrideResult['changed'] ? $overrideResult['diff'] : [];
        $saved = $this->persistOverrideConfig($normalizedKey, $override);
        if ($saved) {
            $this->clearCacheByKey($normalizedKey);
        }

        return $saved;
    }

    /**
     * @param string $sportKey
     * @return bool
     */
    public function resetToDefaults(string $sportKey): bool
    {
        $normalizedKey = $this->normalizeSportKey($sportKey);
        if ($normalizedKey === '') {
            return false;
        }

        $saved = $this->persistOverrideConfig($normalizedKey, []);
        if ($saved) {
            $this->clearCacheByKey($normalizedKey);
        }

        return $saved;
    }

    /**
     * @param string $sportKey
     * @return array<string,mixed>
     */
    public function getFormattedConfigsForSport(string $sportKey): array
    {
        $config = $this->stripLegacyAliases($this->getMergedConfig($sportKey));
        $descriptions = [];
        if (isset($config['_descriptions']) && is_array($config['_descriptions'])) {
            $descriptions = $config['_descriptions'];
        }

        $formatted = [
            'period_names' => [],
            'officials' => [
                'value' => [],
                'description' => '',
            ],
            'settings' => [],
        ];

        foreach ((array)($config['period_names'] ?? []) as $periods => $periodName) {
            $descriptionKey = 'period_name_' . $periods;
            $formatted['period_names'][(string)$periods] = [
                'value' => (string)$periodName,
                'description' => isset($descriptions[$descriptionKey])
                    ? (string)$descriptions[$descriptionKey]
                    : '',
            ];
        }

        $formatted['officials']['value'] = $this->normalizeStringList($config['officials'] ?? []);
        $formatted['officials']['description'] = isset($descriptions['officials'])
            ? (string)$descriptions['officials']
            : '';

        $excluded = ['id', 'key', 'name', 'period_names', 'officials', '_descriptions'];
        foreach ($config as $key => $value) {
            if (!is_string($key) || in_array($key, $excluded, true)) {
                continue;
            }

            $formatted['settings'][$key] = [
                'value' => $value,
                'description' => isset($descriptions[$key])
                    ? (string)$descriptions[$key]
                    : '',
            ];
        }

        ksort($formatted['period_names']);
        ksort($formatted['settings']);

        return $formatted;
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getFormattedConfigsForSportById(int $sportId): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [
                'period_names' => [],
                'officials' => ['value' => [], 'description' => ''],
                'settings' => [],
            ];
        }

        return $this->getFormattedConfigsForSport($sportKey);
    }

    /**
     * Get a sport ID for a canonical sport key.
     *
     * @param string $key
     * @return int|null
     */
    public function getIdByKey(string $key): ?int
    {
        $defaults = $this->getAllSportsDefaults();
        $sportKey = $this->normalizeSportKey($key);
        if ($sportKey === '') {
            return null;
        }

        if (!array_key_exists($sportKey, $defaults)) {
            $resolvedKey = $this->findSportKeyByDisplayName($key);
            if ($resolvedKey === null || !array_key_exists($resolvedKey, $defaults)) {
                return null;
            }
            $sportKey = $resolvedKey;
        }

        $configuredId = $defaults[$sportKey]['id'] ?? null;
        if (is_int($configuredId) && $configuredId > 0) {
            return $configuredId;
        }

        return null;
    }

    /**
     * Get a canonical sport key from a legacy numeric sport ID.
     *
     * @param int $id
     * @return string|null
     */
    public function getKeyById(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        foreach ($this->getAllSportsDefaults() as $sportKey => $config) {
            $configuredId = $config['id'] ?? null;
            if (is_int($configuredId) && $configuredId === $id) {
                return $sportKey;
            }
        }

        return null;
    }

    /**
     * Legacy helper returning lowercase sport key-like name by ID.
     *
     * @param int $sportId
     * @return string
     */
    public function getSportName(int $sportId): string
    {
        $sportKey = $this->getKeyById($sportId);

        return $sportKey ?? 'unknown';
    }

    /**
     * Legacy key lookup for callers still passing sport IDs.
     *
     * @param int $sportId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(int $sportId, string $key, mixed $default = null): mixed
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return $default;
        }

        $config = $this->getMergedConfig($sportKey);
        if ($config === []) {
            return $default;
        }

        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value)) {
                return $default;
            }

            if (array_key_exists($segment, $value)) {
                $value = $value[$segment];

                continue;
            }

            if (ctype_digit($segment)) {
                $index = (int)$segment;
                if (array_key_exists($index, $value)) {
                    $value = $value[$index];

                    continue;
                }
            }

            return $default;
        }

        return $value;
    }

    /**
     * @param int $sportId
     * @return array{supported:array<int>,default:int,names:array<int,string>}
     */
    public function getPeriodConfig(int $sportId): array
    {
        $periodNames = $this->normalizePeriodNames($this->getConfig($sportId, 'period_names', []));
        $supportsValue = $this->getConfig($sportId, 'supports_periods', [2, 4]);
        $defaultPeriods = (int)$this->getConfig($sportId, 'default_periods', 4);
        $supported = $this->resolveSupportedPeriods($supportsValue, $periodNames, $defaultPeriods);

        return [
            'supported' => $supported,
            'default' => $defaultPeriods,
            'names' => $periodNames,
        ];
    }

    /**
     * @param int $sportId
     * @param int $periodCount
     * @return string
     */
    public function getPeriodName(int $sportId, int $periodCount): string
    {
        $periodNames = $this->normalizePeriodNames($this->getConfig($sportId, 'period_names', []));
        if (isset($periodNames[$periodCount])) {
            return $periodNames[$periodCount];
        }

        return match ($periodCount) {
            2 => 'Half',
            4 => 'Quarter',
            9 => 'Inning',
            default => 'Period',
        };
    }

    /**
     * @param int $sportId
     * @return array<int,string>
     */
    public function getOfficials(int $sportId): array
    {
        return $this->normalizeStringList($this->getConfig($sportId, 'officials', ['Official 1', 'Official 2']));
    }

    /**
     * @param int $sportId
     * @param string $context
     * @param string $type
     * @return string|null
     */
    public function getStatTable(int $sportId, string $context, string $type): ?string
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return null;
        }

        $config = $this->getMergedConfig($sportKey);
        if (!$this->hasStatsEnabled($config)) {
            return null;
        }

        $table = $this->buildDerivedStatTableName($sportKey, $context, $type);

        return is_string($table) ? $table : null;
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getAllStatTables(int $sportId): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [];
        }

        $config = $this->getMergedConfig($sportKey);
        if (!$this->hasStatsEnabled($config)) {
            return [];
        }

        $tables = [];
        foreach (self::STAT_TABLE_CONTEXTS as $context => $types) {
            foreach ($types as $type) {
                $tableName = $this->buildDerivedStatTableName($sportKey, $context, $type);
                if ($tableName === null) {
                    continue;
                }

                $tables[$context][$type] = $tableName;
            }
        }

        return $tables;
    }

    /**
     * @param int $sportId
     * @param string $type
     * @return array<int|string,mixed>
     */
    public function getStatFields(int $sportId, string $type): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [];
        }

        $config = $this->getMergedConfig($sportKey);
        if (!$this->hasStatsEnabled($config)) {
            return [];
        }

        $fieldType = strtolower(trim($type));
        if (!in_array($fieldType, ['player', 'team', 'opponent'], true)) {
            return [];
        }

        return $this->normalizeStatList($config['stats_tracked'] ?? []);
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getAllStatFields(int $sportId): array
    {
        $playerFields = $this->getStatFields($sportId, 'player');
        if ($playerFields === []) {
            return [];
        }

        return [
            'player' => $playerFields,
            'team' => $playerFields,
            'opponent' => $playerFields,
        ];
    }

    /**
     * @param int $sportId
     * @param string $field
     * @return string
     */
    public function getFieldLabel(int $sportId, string $field): string
    {
        $fieldName = strtoupper(trim($field));
        if ($fieldName === '') {
            return '';
        }

        if (isset(self::DEFAULT_FIELD_LABELS[$fieldName])) {
            return self::DEFAULT_FIELD_LABELS[$fieldName];
        }

        return $fieldName;
    }

    /**
     * @param int $sportId
     * @return array<string,string>
     */
    public function getAllFieldLabels(int $sportId): array
    {
        $fields = $this->getStatFields($sportId, 'player');
        if ($fields === []) {
            return [];
        }

        $labels = [];
        foreach ($fields as $field) {
            if (!is_scalar($field)) {
                continue;
            }

            $fieldName = strtoupper(trim((string)$field));
            if ($fieldName === '') {
                continue;
            }

            $labels[$fieldName] = $this->getFieldLabel($sportId, $fieldName);
        }

        return $labels;
    }

    /**
     * @param int $sportId
     * @param string $field
     * @return array<string,mixed>|null
     */
    public function getCalculatedField(int $sportId, string $field): ?array
    {
        $all = $this->getAllCalculatedFields($sportId);
        if (!isset($all[$field]) || !is_array($all[$field])) {
            return null;
        }

        return $all[$field];
    }

    /**
     * @param int $sportId
     * @return array<string,mixed>
     */
    public function getAllCalculatedFields(int $sportId): array
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return [];
        }

        $config = $this->getMergedConfig($sportKey);
        if (!$this->hasStatsEnabled($config)) {
            return [];
        }

        return self::DEFAULT_CALCULATED_FIELDS_BY_SPORT[$sportKey] ?? [];
    }

    /**
     * Validate period/overtime scoring when scoring type is cumulative.
     *
     * @param int $sportId
     * @param array<string,mixed> $data
     * @return array<int,string>
     */
    public function validatePeriodScores(int $sportId, array $data): array
    {
        $errors = [];

        $scoringType = (string)$this->getConfig($sportId, 'scoring_type', 'cumulative');
        if ($scoringType !== 'cumulative') {
            return $errors;
        }

        $teamScore = isset($data['pts_mur']) ? (int)$data['pts_mur'] : 0;
        $oppScore = isset($data['pts_opp']) ? (int)$data['pts_opp'] : 0;
        $periods = isset($data['periods']) ? (int)$data['periods'] : 2;
        $otPeriods = isset($data['ot']) ? (int)$data['ot'] : 0;

        if ($teamScore === 0 && $oppScore === 0) {
            return $errors;
        }

        $teamPeriodSum = 0;
        $oppPeriodSum = 0;
        $hasPeriodData = false;

        for ($i = 1; $i <= $periods; $i++) {
            $teamKey = "period_{$i}_team";
            $oppKey = "period_{$i}_opponent";

            if (isset($data[$teamKey]) && $data[$teamKey] !== '') {
                $teamPeriodSum += (int)$data[$teamKey];
                $hasPeriodData = true;
            }
            if (isset($data[$oppKey]) && $data[$oppKey] !== '') {
                $oppPeriodSum += (int)$data[$oppKey];
                $hasPeriodData = true;
            }
        }

        $teamOtSum = 0;
        $oppOtSum = 0;

        for ($i = 1; $i <= $otPeriods; $i++) {
            $teamKey = "overtime_{$i}_team";
            $oppKey = "overtime_{$i}_opponent";

            if (isset($data[$teamKey]) && $data[$teamKey] !== '') {
                $teamOtSum += (int)$data[$teamKey];
                $hasPeriodData = true;
            }
            if (isset($data[$oppKey]) && $data[$oppKey] !== '') {
                $oppOtSum += (int)$data[$oppKey];
                $hasPeriodData = true;
            }
        }

        if (!$hasPeriodData) {
            return $errors;
        }

        if ($otPeriods > 0 && $teamPeriodSum !== $oppPeriodSum) {
            $errors[] = sprintf(
                'Regular period scores must be tied when overtime periods exist. Team: %d, Opponent: %d',
                $teamPeriodSum,
                $oppPeriodSum,
            );
        }

        $teamTotalPeriods = $teamPeriodSum + $teamOtSum;
        $oppTotalPeriods = $oppPeriodSum + $oppOtSum;

        if ($teamTotalPeriods !== $teamScore) {
            $errors[] = sprintf(
                'Team period scores (%d) must equal final team score (%d)',
                $teamTotalPeriods,
                $teamScore,
            );
        }

        if ($oppTotalPeriods !== $oppScore) {
            $errors[] = sprintf(
                'Opponent period scores (%d) must equal final opponent score (%d)',
                $oppTotalPeriods,
                $oppScore,
            );
        }

        return $errors;
    }

    /**
     * @param int $sportId
     * @return void
     */
    public function clearCache(int $sportId): void
    {
        $sportKey = $this->getKeyById($sportId);
        if ($sportKey === null) {
            return;
        }

        $this->clearCacheByKey($sportKey);
    }

    /**
     * @param string $sportKey
     * @return void
     */
    private function clearCacheByKey(string $sportKey): void
    {
        Cache::delete(self::MERGED_CACHE_KEY_PREFIX . $this->normalizeSportKey($sportKey), $this->cacheConfig);
    }

    /**
     * @param string $sportKey
     * @return string
     */
    private function getOverrideOptionKey(string $sportKey): string
    {
        return self::OVERRIDE_OPTION_PREFIX . $sportKey;
    }

    /**
     * @param string $sportKey
     * @return array<string,mixed>
     */
    private function getOverrideConfig(string $sportKey): array
    {
        $raw = $this->siteOptionService->getOptionValue($this->getOverrideOptionKey($sportKey));
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param string $sportKey
     * @param array<string,mixed> $override
     */
    private function persistOverrideConfig(string $sportKey, array $override): bool
    {
        $optionKey = $this->getOverrideOptionKey($sportKey);

        if ($override === []) {
            return $this->deleteOverrideRow($optionKey);
        }

        $payload = json_encode($override, JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            return false;
        }

        return $this->siteOptionService->setOptionValue($optionKey, $payload);
    }

    /**
     * @param string $optionKey
     * @return bool
     */
    private function deleteOverrideRow(string $optionKey): bool
    {
        try {
            /** @var \App\Model\Table\SiteOptionsTable $table */
            $table = TableRegistry::getTableLocator()->get('SiteOptions');
            $table->deleteAll(['option_key' => $optionKey]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string,mixed> $base
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function mergeConfigRecursive(array $base, array $override): array
    {
        $merged = $base;

        foreach ($override as $key => $value) {
            if (!array_key_exists($key, $merged)) {
                $merged[$key] = $value;

                continue;
            }

            // period_names should be fully replaced when explicitly overridden
            // so custom sport period sets can remove default entries.
            if ($key === 'period_names' && is_array($value)) {
                $merged[$key] = $value;

                continue;
            }

            if (is_array($merged[$key]) && is_array($value)) {
                if (array_is_list($merged[$key]) || array_is_list($value)) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] = $this->mergeConfigRecursive($merged[$key], $value);
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param mixed $default
     * @param mixed $current
     * @return array{changed:bool,diff:mixed}
     */
    private function diffConfigRecursive(mixed $default, mixed $current): array
    {
        if (is_array($default) && is_array($current)) {
            if (array_is_list($default) || array_is_list($current)) {
                return [
                    'changed' => $default !== $current,
                    'diff' => $current,
                ];
            }

            $diff = [];
            $changed = false;
            foreach ($current as $key => $value) {
                if (!array_key_exists($key, $default)) {
                    $diff[$key] = $value;
                    $changed = true;

                    continue;
                }

                $childDiff = $this->diffConfigRecursive($default[$key], $value);
                if ($childDiff['changed']) {
                    $diff[$key] = $childDiff['diff'];
                    $changed = true;
                }
            }

            return [
                'changed' => $changed,
                'diff' => $diff,
            ];
        }

        return [
            'changed' => $default !== $current,
            'diff' => $current,
        ];
    }

    /**
     * @param string $sportKey
     * @return string
     */
    private function normalizeSportKey(string $sportKey): string
    {
        $normalized = strtolower(trim($sportKey));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?: '';

        return trim($normalized, '_');
    }

    /**
     * @param string $sportKey
     * @return string
     */
    private function humanizeKey(string $sportKey): string
    {
        $clean = trim(str_replace(['_', '-'], ' ', $sportKey));

        return ucwords($clean);
    }

    /**
     * @param string $displayName
     * @return string|null
     */
    private function findSportKeyByDisplayName(string $displayName): ?string
    {
        $targetName = strtolower(trim($displayName));
        $targetKey = $this->normalizeSportKey($displayName);

        foreach ($this->getAllSportsDefaults() as $sportKey => $config) {
            $name = strtolower((string)($config['name'] ?? ''));
            if ($name === $targetName) {
                return $sportKey;
            }
            if ($sportKey === $targetKey) {
                return $sportKey;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $config
     * @param string $sportKey
     * @param string $sportDisplayName
     * @return array<string,mixed>
     */
    private function normalizeConfig(array $config, string $sportKey, string $sportDisplayName): array
    {
        $periodNamesSource = $config['period_names'] ?? $config['periodNames'] ?? [];
        if (!is_array($periodNamesSource)) {
            $periodNamesSource = [];
        }

        foreach ($config as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (preg_match('/^period_name_(\d+)$/', $key, $matches) === 1) {
                $periodNamesSource[(int)$matches[1]] = $value;
            }
        }

        $periodNames = $this->normalizePeriodNames($periodNamesSource);

        $supportsPeriods = $this->normalizeSupportsPeriods(
            $config['supports_periods'] ?? $config['periods'] ?? array_keys($periodNames),
            $periodNames,
        );

        $periodNameKeys = array_map('intval', array_keys($periodNames));
        $firstPeriodNameKey = $periodNameKeys !== [] ? (int)$periodNameKeys[0] : 4;

        $defaultPeriodsValue = $config['default_periods'] ?? $config['defaultPeriods'] ?? null;
        $defaultPeriods = is_numeric((string)$defaultPeriodsValue)
            ? (int)$defaultPeriodsValue
            : $firstPeriodNameKey;

        if ($defaultPeriods <= 0) {
            $defaultPeriods = 4;
        }
        if (
            is_array($supportsPeriods)
            && $supportsPeriods !== []
            && !in_array($defaultPeriods, $supportsPeriods, true)
        ) {
            $defaultPeriods = (int)$supportsPeriods[0];
        }

        $officials = $this->normalizeStringList($config['officials'] ?? []);
        $statsTracked = $this->normalizeStatList($config['stats_tracked'] ?? $config['statsTracked'] ?? []);
        $hasStats = $this->normalizeBoolean(
            $config['has_stats'] ?? $config['hasStats'] ?? ($statsTracked !== []),
            $statsTracked !== [],
        );

        $sportActive = $this->normalizeBoolean($config['sport_active'] ?? $config['sportActive'] ?? true, true);

        $idValue = $config['id'] ?? null;
        $sportId = is_numeric((string)$idValue) && (int)$idValue > 0
            ? (int)$idValue
            : null;

        $normalized = [
            'id' => $sportId,
            'key' => $sportKey,
            'name' => (string)($config['name'] ?? $sportDisplayName),
            'sport_active' => $sportActive,
            'has_stats' => $hasStats,
            'stats_tracked' => $statsTracked,
            'period_names' => $periodNames,
            'supports_periods' => $supportsPeriods,
            'default_periods' => $defaultPeriods,
            'overtime_name' => (string)($config['overtime_name'] ?? $config['overtimeName'] ?? 'OT'),
            'officials' => $officials,
            'scoring_type' => strtolower((string)($config['scoring_type'] ?? $config['scoringType'] ?? 'cumulative')),
        ];

        $reserved = [
            'id', 'key', 'name',
            'sport_active', 'sportActive',
            'has_stats', 'hasStats',
            'stats_tracked', 'statsTracked',
            'period_names', 'periodNames',
            'supports_periods', 'periods', 'default_periods', 'defaultPeriods',
            'overtime_name', 'overtimeName', 'officials',
            'scoring_type', 'scoringType',
        ];

        foreach ($config as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (in_array($key, $reserved, true)) {
                continue;
            }
            if (preg_match('/^period_name_(\d+)$/', $key) === 1) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $this->appendLegacyAliases($normalized);
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function appendLegacyAliases(array $config): array
    {
        if (!isset($config['id']) || !is_int($config['id']) || $config['id'] <= 0) {
            unset($config['id']);
        }

        $config['sport_key'] = (string)($config['key'] ?? '');
        $supports = $config['supports_periods'] ?? [];
        if ($this->isAnyPeriodsValue($supports)) {
            $derived = array_map('intval', array_keys((array)($config['period_names'] ?? [])));
            if (
                $derived === []
                && isset($config['default_periods'])
                && is_numeric((string)$config['default_periods'])
            ) {
                $derived = [(int)$config['default_periods']];
            }
            $config['periods'] = $derived;
        } else {
            $config['periods'] = is_array($supports) ? $supports : [];
        }

        $config['defaultPeriods'] = $config['default_periods'] ?? 4;
        $config['periodNames'] = $config['period_names'] ?? [];
        $config['scoringType'] = $config['scoring_type'] ?? 'cumulative';
        $config['sportActive'] = $config['sport_active'] ?? true;
        $config['hasStats'] = $config['has_stats'] ?? false;
        $config['statsTracked'] = $config['stats_tracked'] ?? [];

        $periodNames = $config['period_names'] ?? [];
        if (is_array($periodNames)) {
            foreach ($periodNames as $periods => $name) {
                $periodCount = is_numeric((string)$periods) ? (int)$periods : null;
                if ($periodCount === null || $periodCount <= 0) {
                    continue;
                }

                $config['period_name_' . $periodCount] = (string)$name;
            }
        }

        return $config;
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function stripLegacyAliases(array $config): array
    {
        $aliases = [
            'sport_key',
            'periods',
            'defaultPeriods',
            'periodNames',
            'scoringType',
            'sportActive',
            'hasStats',
            'statsTracked',
        ];

        foreach ($aliases as $alias) {
            unset($config[$alias]);
        }

        foreach (array_keys($config) as $key) {
            if (!is_string($key)) {
                continue;
            }
            if (preg_match('/^period_name_(\d+)$/', $key) === 1) {
                unset($config[$key]);
            }
        }

        return $config;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $label = trim((string)$item);
            if ($label === '') {
                continue;
            }
            $result[] = $label;
        }

        return array_values(array_unique($result));
    }

    /**
     * @param mixed $value
     * @return array<int>
     */
    private function normalizeIntList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_numeric((string)$item)) {
                continue;
            }
            $result[] = (int)$item;
        }

        $result = array_values(array_unique($result));
        sort($result);

        return $result;
    }

    /**
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    private function normalizeBoolean(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int)$value) !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeStatList(mixed $value): array
    {
        $items = $this->normalizeStringList($value);
        $normalized = [];

        foreach ($items as $item) {
            $field = strtoupper(trim($item));
            if ($field === '') {
                continue;
            }
            $normalized[] = $field;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isAnyPeriodsValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, [self::ANY_PERIODS_VALUE, '*', 'all'], true);
    }

    /**
     * @param mixed $value
     * @param array<int,string> $periodNames
     * @return array<int>|string
     */
    private function normalizeSupportsPeriods(mixed $value, array $periodNames): array|string
    {
        if ($this->isAnyPeriodsValue($value)) {
            return self::ANY_PERIODS_VALUE;
        }

        $normalized = $this->normalizeIntList($value);
        if ($normalized === [] && $periodNames !== []) {
            $normalized = array_map('intval', array_keys($periodNames));
        }

        return $normalized;
    }

    /**
     * @param mixed $supportsPeriods
     * @param array<int,string> $periodNames
     * @param int $defaultPeriods
     * @return array<int>
     */
    private function resolveSupportedPeriods(mixed $supportsPeriods, array $periodNames, int $defaultPeriods): array
    {
        if ($this->isAnyPeriodsValue($supportsPeriods)) {
            $derived = array_map('intval', array_keys($periodNames));
            if ($derived !== []) {
                sort($derived);

                return $derived;
            }

            return $defaultPeriods > 0 ? [$defaultPeriods] : [2, 4];
        }

        $normalized = $this->normalizeIntList($supportsPeriods);
        if ($normalized !== []) {
            return $normalized;
        }

        $derived = array_map('intval', array_keys($periodNames));
        if ($derived !== []) {
            sort($derived);

            return $derived;
        }

        return $defaultPeriods > 0 ? [$defaultPeriods] : [2, 4];
    }

    /**
     * @param array<string,mixed> $config
     * @return bool
     */
    private function hasStatsEnabled(array $config): bool
    {
        $statsTracked = $this->normalizeStatList($config['stats_tracked'] ?? []);
        $defaultHasStats = $statsTracked !== [];

        return $this->normalizeBoolean($config['has_stats'] ?? $defaultHasStats, $defaultHasStats);
    }

    /**
     * @param string $sportKey
     * @param string $context
     * @param string $type
     * @return string|null
     */
    private function buildDerivedStatTableName(string $sportKey, string $context, string $type): ?string
    {
        $normalizedContext = strtolower(trim($context));
        $normalizedType = strtolower(trim($type));

        if (!isset(self::STAT_TABLE_CONTEXTS[$normalizedContext])) {
            return null;
        }
        if (!in_array($normalizedType, self::STAT_TABLE_CONTEXTS[$normalizedContext], true)) {
            return null;
        }

        $entitySuffix = $this->mapStatEntityType($normalizedType);
        if ($entitySuffix === null) {
            return null;
        }

        $sportSlug = self::SPORT_TABLE_SLUGS[$sportKey] ?? $sportKey;

        return sprintf('stat_%s_%s_%s', $sportSlug, $normalizedContext, $entitySuffix);
    }

    /**
     * @param string $type
     * @return string|null
     */
    private function mapStatEntityType(string $type): ?string
    {
        return match ($type) {
            'player' => 'person',
            'team' => 'team',
            'opponent' => 'opponent',
            'box' => 'box',
            default => null,
        };
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizePeriodNames(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $periods => $label) {
            $periodCount = is_numeric((string)$periods) ? (int)$periods : null;
            if ($periodCount === null || $periodCount <= 0) {
                continue;
            }

            if (is_array($label) && isset($label['value'])) {
                $label = $label['value'];
            }

            $labelString = trim((string)$label);
            if ($labelString === '') {
                continue;
            }

            $normalized[$periodCount] = $labelString;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function fallbackDefaults(): array
    {
        $basketball = [
            'id' => 1,
            'key' => 'basketball',
            'name' => 'Basketball',
            'sport_active' => true,
            'has_stats' => true,
            'stats_tracked' => ['MIN', 'FGM', 'FGA', 'PTS'],
            'period_names' => [2 => 'Half', 4 => 'Quarter'],
            'supports_periods' => self::ANY_PERIODS_VALUE,
            'default_periods' => 4,
            'overtime_name' => 'OT',
            'officials' => ['Referee 1', 'Referee 2', 'Umpire'],
            'scoring_type' => 'cumulative',
        ];

        return [
            'basketball' => $this->stripLegacyAliases($this->normalizeConfig($basketball, 'basketball', 'Basketball')),
        ];
    }
}
