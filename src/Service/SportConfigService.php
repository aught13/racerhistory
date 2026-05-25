<?php
declare(strict_types=1);

namespace App\Service;

use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Sport Configuration Service
 *
 * Provides a central access point for sport configurations with fallbacks to hardcoded defaults
 */
class SportConfigService
{
    use LocatorAwareTrait;
    use ServiceAwareTrait;

    /**
     * Hard-coded sport configuration defaults that serve as fallbacks
     * These act as guardrails for the application
     */
    protected array $defaults = [
        // Basketball defaults
        'basketball' => [
            'periods' => [2, 4],
            'defaultPeriods' => 4,
            'periodNames' => [
                2 => 'Half',
                4 => 'Quarter',
            ],
            'officials' => ['Referee', 'Umpire', 'Alternate'],
            'scoringType' => 'cumulative',
            'statTables' => [
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'opponent' => 'stat_basket_game_opponent',
                    'player' => 'stat_basket_game_person',
                    'box' => 'stat_basket_game_box',
                ],
                'season' => [
                    'team' => 'stat_basket_season_team',
                    'opponent' => 'stat_basket_season_opponent',
                    'player' => 'stat_basket_season_person',
                ],
            ],
            'statFields' => [
                'player' => [
                    'MIN', 'FGM', 'FGA', '3PM', '3PA', 'FTM', 'FTA',
                    'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TO', 'PF', 'PTS',
                ],
                'team' => [
                    'ORB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TO', 'PF',
                    'FGM', 'FGA', '3PM', '3PA', 'FTM', 'FTA', 'PTS',
                    'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
                ],
                'opponent' => [
                    'ORB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TO', 'PF',
                    'FGM', 'FGA', '3PM', '3PA', 'FTM', 'FTA', 'PTS',
                    'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
                ],
            ],
            'fieldLabels' => [
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
                'OTO' => 'Points off Turnovers',
                'SND' => '2nd Chance Points',
                'FB' => 'Fast Break Points',
                'BN' => 'Bench Points',
                'TIED' => 'Times Tied',
                'LC' => 'Lead Changes',
            ],
            'calculatedFields' => [
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
        ],
        // Football defaults
        'football' => [
            'periods' => [4],
            'defaultPeriods' => 4,
            'periodNames' => [
                4 => 'Quarter',
            ],
            'officials' => [
                'Referee', 'Umpire', 'Head Linesman',
                'Line Judge', 'Field Judge', 'Side Judge', 'Back Judge',
            ],
            'scoringType' => 'cumulative',
            'statTables' => [
                'game' => [
                    'team' => 'stat_football_game_team',
                    'opponent' => 'stat_football_game_opponent',
                    'player' => 'stat_football_game_person',
                    'box' => 'stat_football_game_box',
                ],
                'season' => [
                    'team' => 'stat_football_season_team',
                    'opponent' => 'stat_football_season_opponent',
                    'player' => 'stat_football_season_person',
                ],
            ],
            'statFields' => [
                'player' => [
                    'offense' => ['COMP', 'ATT', 'YDS', 'TD', 'INT', 'RUSH', 'RYDS', 'RTD', 'REC', 'RECYDS', 'RECTD'],
                    'defense' => ['TKL', 'AST', 'SACK', 'FF', 'FR', 'INT', 'PD', 'TD'],
                    'special' => ['FGM', 'FGA', 'XPM', 'XPA', 'PUNTS', 'PAVG', 'TB', 'I20'],
                ],
                'team' => [
                    'COMP', 'ATT', 'YDS', 'TD', 'INT', 'RUSH', 'RYDS', 'RTD',
                    'REC', 'RECYDS', 'RECTD', 'TKL', 'AST', 'SACK', 'FF', 'FR',
                    'DINT', 'PD', 'DTD', 'FGM', 'FGA', 'XPM', 'XPA',
                    'PUNTS', 'PAVG', 'TB', 'I20',
                ],
                'opponent' => [
                    'COMP', 'ATT', 'YDS', 'TD', 'INT', 'RUSH', 'RYDS', 'RTD',
                    'REC', 'RECYDS', 'RECTD', 'TKL', 'AST', 'SACK', 'FF', 'FR',
                    'DINT', 'PD', 'DTD', 'FGM', 'FGA', 'XPM', 'XPA',
                    'PUNTS', 'PAVG', 'TB', 'I20',
                ],
            ],
            'fieldLabels' => [
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
                'AST' => 'Assisted Tackles',
                'SACK' => 'Sacks',
                'FF' => 'Forced Fumbles',
                'FR' => 'Fumble Recoveries',
                'DINT' => 'Interceptions',
                'PD' => 'Passes Defended',
                'DTD' => 'Defensive Touchdowns',
                'FGM' => 'Field Goals Made',
                'FGA' => 'Field Goals Attempted',
                'XPM' => 'Extra Points Made',
                'XPA' => 'Extra Points Attempted',
                'PUNTS' => 'Punts',
                'PAVG' => 'Punting Average',
                'TB' => 'Touchbacks',
                'I20' => 'Inside 20',
            ],
            'calculatedFields' => [
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
                'FG%' => [
                    'formula' => 'FGM / FGA * 100',
                    'condition' => 'FGA > 0',
                    'format' => '%.1f%%',
                ],
                'XP%' => [
                    'formula' => 'XPM / XPA * 100',
                    'condition' => 'XPA > 0',
                    'format' => '%.1f%%',
                ],
            ],
        ],
        // Add more sports as needed
    ];

    /**
     * Cache configuration key
     *
     * @var string
     */
    protected string $cacheConfig = 'sport_config';

    /**
     * Constructor
     *
     * Sets up the cache configuration if it doesn't exist
     */
    public function __construct()
    {
        if (!Cache::getConfig($this->cacheConfig)) {
            Cache::setConfig($this->cacheConfig, [
                'className' => 'File',
                'duration' => '+1 day',
                'path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sport_config' . DIRECTORY_SEPARATOR,
                'prefix' => 'sport_',
            ]);
        }
    }

    /**
     * Get a configuration value for a sport with fallback to defaults
     *
     * @param int $sportId Sport ID
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */

    /**
     * Get a configuration value for a sport with fallback to defaults
     *
     * @param int $sportId Sport ID
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function getConfig(int $sportId, string $key, mixed $default = null): mixed
    {
        // Get sport name first (already lowercase from getSportName)
        $sportName = $this->getSportName($sportId);

        // Try database config first
        $value = $this->getDbConfig($sportId, $key);
        if ($value !== null) {
            return $value;
        }

        // Check hard defaults by sport type
        $keyParts = explode('.', $key);
        $config = $this->defaults[$sportName] ?? [];

        foreach ($keyParts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    /**
     * Get sport name by ID
     *
     * @param int $sportId Sport ID
     * @return string Sport name (lowercase)
     */
    public function getSportName(int $sportId): string
    {
        $cacheKey = "sport_name_{$sportId}";
        $sportName = Cache::read($cacheKey, $this->cacheConfig);

        // Don't use cached 'unknown' values - they indicate previous lookup failures
        if ($sportName === null || $sportName === 'unknown') {
            $sportsTable = $this->fetchTable('Sports');
            $sport = $sportsTable->find()
                ->select(['sport_name'])
                ->where(['id' => $sportId])
                ->first();

            $sportName = $sport ? strtolower((string)$sport->get('sport_name')) : 'unknown';

            // Only cache successful lookups
            if ($sportName !== 'unknown') {
                Cache::write($cacheKey, $sportName, $this->cacheConfig);
            }
        }

        return $sportName;
    }

    /**
     * Get configuration from database
     *
     * @param int $sportId Sport ID
     * @param string $key Configuration key
     * @return mixed|null Configuration value or null if not found
     */

    /**
     * Get configuration from database
     *
     * @param int $sportId Sport ID
     * @param string $key Configuration key
     * @return mixed|null Configuration value or null if not found
     */
    protected function getDbConfig(int $sportId, string $key): mixed
    {
        $cacheKey = "sport_config_{$sportId}_{$key}";
        $value = Cache::read($cacheKey, $this->cacheConfig);

        if ($value === null) {
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            $config = $sportConfigsTable->find()
                ->select(['config_value'])
                ->where([
                    'sport_id' => $sportId,
                    'config_key' => $key,
                ])
                ->first();

            if (!$config) {
                return null;
            }

            // Try to decode JSON values
            $value = $config->get('config_value');
            $decoded = json_decode($value, true);
            $value = $decoded ?? $value;

            Cache::write($cacheKey, $value, $this->cacheConfig);
        }

        return $value;
    }

    /**
     * Get periods configuration for a sport
     *
     * @param int $sportId Sport ID
     * @return array Period configuration with supported periods and default period count
     */
    public function getPeriodConfig(int $sportId): array
    {
        return [
            'supported' => $this->getConfig($sportId, 'periods', [2, 4]),
            'default' => $this->getConfig($sportId, 'defaultPeriods', 4),
            'names' => $this->getConfig($sportId, 'periodNames', [
                2 => 'Half',
                4 => 'Quarter',
            ]),
        ];
    }

    /**
     * Get period name for a specific sport and period count
     *
     * @param int $sportId Sport ID
     * @param int $periodCount Number of periods
     * @return string Period name (e.g. "Quarter", "Half")
     */
    public function getPeriodName(int $sportId, int $periodCount): string
    {
        $periodNames = $this->getConfig($sportId, 'periodNames', []);

        if (isset($periodNames[$periodCount])) {
            return $periodNames[$periodCount];
        }

        // Default period names if not configured
        return match ($periodCount) {
            2 => 'Half',
            4 => 'Quarter',
            9 => 'Inning',
            default => 'Period',
        };
    }

    /**
     * Get officials list for a sport
     *
     * @param int $sportId Sport ID
     * @return array List of official titles
     */
    public function getOfficials(int $sportId): array
    {
        return $this->getConfig($sportId, 'officials', ['Official 1', 'Official 2']);
    }

    /**
     * Get stat table name for a sport and context
     *
     * @param int $sportId Sport ID
     * @param string $context 'game' or 'season'
     * @param string $type 'team', 'player', 'opponent', etc.
     * @return string|null Table name or null if not configured
     */
    public function getStatTable(int $sportId, string $context, string $type): ?string
    {
        return $this->getConfig($sportId, "statTables.{$context}.{$type}");
    }

    /**
     * Get all stat tables for a sport
     *
     * @param int $sportId Sport ID
     * @return array Stat tables by context and type
     */
    public function getAllStatTables(int $sportId): array
    {
        return $this->getConfig($sportId, 'statTables', []);
    }

    /**
     * Get stat fields for a sport and entity type
     *
     * @param int $sportId Sport ID
     * @param string $type 'team', 'player', 'opponent', etc.
     * @return array List of stat fields
     */
    public function getStatFields(int $sportId, string $type): array
    {
        return $this->getConfig($sportId, "statFields.{$type}", []);
    }

    /**
     * Get all stat fields for a sport
     *
     * @param int $sportId Sport ID
     * @return array All stat fields by type
     */
    public function getAllStatFields(int $sportId): array
    {
        return $this->getConfig($sportId, 'statFields', []);
    }

    /**
     * Get field label for a stat field
     *
     * @param int $sportId Sport ID
     * @param string $field Field code
     * @return string Human-readable field label
     */
    public function getFieldLabel(int $sportId, string $field): string
    {
        $labels = $this->getConfig($sportId, 'fieldLabels', []);

        return $labels[$field] ?? $field;
    }

    /**
     * Get all field labels for a sport
     *
     * @param int $sportId Sport ID
     * @return array Field labels keyed by field code
     */
    public function getAllFieldLabels(int $sportId): array
    {
        return $this->getConfig($sportId, 'fieldLabels', []);
    }

    /**
     * Get calculated field formula
     *
     * @param int $sportId Sport ID
     * @param string $field Calculated field code
     * @return array|null Calculation info or null if not found
     */
    public function getCalculatedField(int $sportId, string $field): ?array
    {
        $calculatedFields = $this->getConfig($sportId, 'calculatedFields', []);

        return $calculatedFields[$field] ?? null;
    }

    /**
     * Get all calculated fields for a sport
     *
     * @param int $sportId Sport ID
     * @return array Calculated fields info
     */
    public function getAllCalculatedFields(int $sportId): array
    {
        return $this->getConfig($sportId, 'calculatedFields', []);
    }

    /**
     * Validate period scores against final scores for sports with cumulative scoring.
     *
     * This validates that period and overtime scores sum correctly to final scores
     * for any sport configured with scoring_type = 'cumulative' (basketball, football, hockey, etc.).
     *
     * @param int $sportId Sport ID
     * @param array $data Game and EAV data containing scores and period data
     * @return array Error messages (empty if valid)
     */
    public function validatePeriodScores(int $sportId, array $data): array
    {
        $errors = [];

        // Check if this sport uses cumulative scoring
        $scoringType = $this->getConfig($sportId, 'scoringType', 'cumulative');
        if ($scoringType !== 'cumulative') {
            return $errors; // No validation needed for non-cumulative sports
        }

        $teamScore = isset($data['pts_mur']) ? (int)$data['pts_mur'] : 0;
        $oppScore = isset($data['pts_opp']) ? (int)$data['pts_opp'] : 0;
        $periods = isset($data['periods']) ? (int)$data['periods'] : 2;
        $otPeriods = isset($data['ot']) ? (int)$data['ot'] : 0;

        // Only validate if scores are present
        if ($teamScore === 0 && $oppScore === 0) {
            return $errors;
        }

        // Calculate sum of regular period scores
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

        // Calculate sum of overtime period scores
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

        // Only validate if period data was provided
        if (!$hasPeriodData) {
            return $errors;
        }

        // If there are OT periods, regular periods must be tied
        if ($otPeriods > 0 && $teamPeriodSum !== $oppPeriodSum) {
            $errors[] = sprintf(
                'Regular period scores must be tied when overtime periods exist. ' .
                'Team: %d, Opponent: %d',
                $teamPeriodSum,
                $oppPeriodSum,
            );
        }

        // Total period + OT must equal final score
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
     * Clear the configuration cache for a sport
     *
     * @param int $sportId Sport ID
     * @return void
     */
    public function clearCache(int $sportId): void
    {
        Cache::deleteMany([
            "sport_name_{$sportId}",
            "sport_config_{$sportId}_*",
        ], $this->cacheConfig);
    }
}
