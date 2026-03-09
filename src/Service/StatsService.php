<?php
declare(strict_types=1);

namespace App\Service;

use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * StatsService
 *
 * Generic statistics service that coordinates sport-specific stat services.
 * Acts as a facade/coordinator for sport-specific statistics operations,
 * delegating to the appropriate sport service based on sport ID/name.
 *
 * This service eliminates the need for controllers to know about specific
 * sport implementations (e.g., BasketballStatsService). Controllers only
 * interact with this generic service, which routes to the correct sport service.
 */
class StatsService
{
    use LocatorAwareTrait;
    use ServiceAwareTrait;

    /**
     * SportConfigService instance
     *
     * @var \App\Service\SportConfigService
     */
    protected SportConfigService $sportConfig;

    /**
     * Map of sport names to their stat service class names
     *
     * @var array<string, string>
     */
    protected array $sportServiceMap = [
        'basketball' => BasketballStatsService::class,
        // Future sports will be added here:
        // 'football' => FootballStatsService::class,
        // 'soccer' => SoccerStatsService::class,
    ];

    /**
     * Map of sport names to the season stats element used in the public site
     *
     * @var array<string, string>
     */
    protected array $seasonStatsElements = [
        'basketball' => 'Seasons/basketball_season_stats',
    ];

    /**
     * Map of sport names to the game stats element used in the public site
     *
     * @var array<string, string>
     */
    protected array $gameStatsElements = [
        'basketball' => 'Games/basketball_game_stats',
    ];

    /**
     * Map of sport names to the person game log element used in the public site
     *
     * @var array<string, string>
     */
    protected array $personGameLogElements = [
        'basketball' => 'People/basketball_game_log',
    ];

    /**
     * Cached sport service instances
     *
     * @var array<string, object>
     */
    protected array $serviceCache = [];

    /**
     * Constructor
     *
     * @param \App\Service\SportConfigService|null $sportConfig Sport config service
     */
    public function __construct(?SportConfigService $sportConfig = null)
    {
        $this->sportConfig = $sportConfig ?? $this->loadService('SportConfig', [], false);
    }

    /**
     * Get game statistics for any sport
     *
     * Delegates to the appropriate sport-specific service based on the game's sport.
     *
     * @param int $gameId Game ID
     * @return array|null Statistics array or null if not available
     */
    public function getGameStats(int $gameId): ?array
    {
        $sportId = $this->getGameSportId($gameId);
        if (!$sportId) {
            return null;
        }

        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getGameStats')) {
            return null;
        }

        return $service->getGameStats($gameId);
    }

    /**
     * Get season statistics for any sport
     *
     * Delegates to the appropriate sport-specific service based on the team season's sport.
     *
     * @param int $teamSeasonId Team Season ID
     * @return array|null Statistics array with keys: playerStats, teamStats, opponentStats
     *                     Returns null if not available
     */
    public function getSeasonStats(int $teamSeasonId): ?array
    {
        $sportId = $this->getTeamSeasonSportId($teamSeasonId);
        if (!$sportId) {
            return null;
        }

        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getSeasonStats')) {
            return null;
        }

        return $service->getSeasonStats($teamSeasonId);
    }

    /**
     * Get the element path for the season stats section for a given team season
     *
     * @param int $teamSeasonId Team season ID
     * @return string|null Element path or null if not configured
     */
    public function getSeasonStatsElement(int $teamSeasonId): ?string
    {
        $sportId = $this->getTeamSeasonSportId($teamSeasonId);
        if (!$sportId) {
            return null;
        }

        $sportName = $this->sportConfig->getSportName($sportId);

        return $this->seasonStatsElements[$sportName] ?? null;
    }

    /**
     * Get the element path for the game stats section for a given game
     *
     * @param int $gameId Game ID
     * @return string|null Element path or null if not configured
     */
    public function getGameStatsElement(int $gameId): ?string
    {
        $sportId = $this->getGameSportId($gameId);
        if (!$sportId) {
            return null;
        }

        $sportName = $this->sportConfig->getSportName($sportId);

        return $this->gameStatsElements[$sportName] ?? null;
    }

    /**
     * Get the element path for a person's game log for a given sport
     *
     * @param int $sportId Sport ID
     * @return string|null Element path or null if not configured
     */
    public function getPersonGameLogElement(int $sportId): ?string
    {
        $sportName = $this->sportConfig->getSportName($sportId);
        if (!$sportName) {
            return null;
        }

        return $this->personGameLogElements[$sportName] ?? null;
    }

    /**
     * Get the visible column list for a season stats table
     *
     * @param int $teamSeasonId Team season ID
     * @param array|null $seasonStats Season stats payload
     * @return array<string, string> Column key/label pairs
     */
    public function getSeasonStatsColumns(int $teamSeasonId, ?array $seasonStats): array
    {
        if (empty($seasonStats)) {
            return [];
        }

        $sportId = $this->getTeamSeasonSportId($teamSeasonId);
        if (!$sportId) {
            return [];
        }

        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getSeasonStatColumns')) {
            return [];
        }

        return $service->getSeasonStatColumns(
            $seasonStats['playerStats'] ?? null,
            $seasonStats['teamStats'] ?? null,
            $seasonStats['opponentStats'] ?? null,
        );
    }

    /**
     * Get a person's season statistics for a given sport context
     *
     * Delegates to the appropriate sport-specific service based on the given sport id.
     *
     * @param int $sportId Sport ID
     * @param int $teamSeasonRosterId Team season roster ID for the person
     * @return object|null Season stats entity or null if not available
     */
    public function getPersonSeasonStats(int $sportId, int $teamSeasonRosterId): ?object
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getPersonSeasonStats')) {
            return null;
        }

        return $service->getPersonSeasonStats($teamSeasonRosterId);
    }

    /**
     * Get a person's game statistics grouped by game for a given sport context
     *
     * Delegates to the appropriate sport-specific service based on the given sport id.
     *
     * @param int $sportId Sport ID
     * @param int $teamSeasonRosterId Team season roster ID for the person
     * @return array<int, array{game: object, stats: array<int, object>}>
     */
    public function getPersonGameStats(int $sportId, int $teamSeasonRosterId): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getPersonGameStats')) {
            return [];
        }

        return $service->getPersonGameStats($teamSeasonRosterId);
    }

    /**
     * Initialize empty stat totals array for a sport
     *
     * Returns a zeroed array of all stat fields for the given sport.
     * Useful for career totals initialization.
     *
     * @param int $sportId Sport ID
     * @param string $type Stat type ('player', 'team', 'opponent')
     * @return array Zeroed stat fields
     */
    public function initializeStats(int $sportId, string $type = 'player'): array
    {
        $service = $this->getSportService($sportId);
        if ($service && method_exists($service, 'initializeStats')) {
            return $service->initializeStats($type);
        }

        // Fallback: get field list from config and zero them
        $fields = $this->sportConfig->getStatFields($sportId, $type);
        $stats = [];
        foreach ($fields as $field) {
            // Handle nested arrays (e.g., football offense/defense/special)
            if (is_array($field)) {
                foreach ($field as $subField) {
                    $stats[$subField] = 0;
                }
            } else {
                $stats[$field] = 0;
            }
        }

        return $stats;
    }

    /**
     * Add season stats to career totals for a sport
     *
     * Delegates to sport-specific service for proper stat aggregation logic.
     *
     * @param int $sportId Sport ID
     * @param array $totals Career totals array (modified by reference)
     * @param object $seasonStats Season stats entity
     * @return void
     */
    public function addSeasonStats(int $sportId, array &$totals, object $seasonStats): void
    {
        $service = $this->getSportService($sportId);
        if ($service && method_exists($service, 'addSeasonStats')) {
            $service->addSeasonStats($totals, $seasonStats);

            return;
        }

        // Fallback: simple addition of all numeric fields
        $fields = $this->sportConfig->getStatFields($sportId, 'player');
        foreach ($fields as $field) {
            if (is_array($field)) {
                // Handle nested field arrays
                foreach ($field as $subField) {
                    $this->addFieldValue($totals, $seasonStats, $subField);
                }
            } else {
                $this->addFieldValue($totals, $seasonStats, $field);
            }
        }
    }

    /**
     * Add a single field value from season stats to totals
     *
     * @param array $totals Totals array (modified by reference)
     * @param object $seasonStats Season stats entity
     * @param string $field Field name
     * @return void
     */
    protected function addFieldValue(array &$totals, object $seasonStats, string $field): void
    {
        if (!isset($totals[$field])) {
            $totals[$field] = 0;
        }

        $value = $seasonStats->$field ?? 0;
        $totals[$field] += is_numeric($value) ? (int)$value : 0;
    }

    /**
     * Get the sport-specific service for a given sport ID
     *
     * @param int $sportId Sport ID
     * @return object|null Sport-specific service instance or null if not found
     */
    protected function getSportService(int $sportId): ?object
    {
        $sportName = $this->sportConfig->getSportName($sportId);
        if (!$sportName || $sportName === 'unknown') {
            return null;
        }

        // Return cached service if available
        if (isset($this->serviceCache[$sportName])) {
            return $this->serviceCache[$sportName];
        }

        // Check if we have a service for this sport
        if (!isset($this->sportServiceMap[$sportName])) {
            return null;
        }

        $serviceClass = $this->sportServiceMap[$sportName];

        // Instantiate and cache the service
        $service = new $serviceClass();
        $this->serviceCache[$sportName] = $service;

        return $service;
    }

    /**
     * Get sport ID for a given game
     *
     * @param int $gameId Game ID
     * @return int|null Sport ID or null if not found
     */
    protected function getGameSportId(int $gameId): ?int
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        $game = $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $gameId])
            ->first();

        if (!$game || !$game->team_season || !$game->team_season->team || !$game->team_season->team->sport) {
            return null;
        }

        return $game->team_season->team->sport->id;
    }

    /**
     * Get sport ID for a given team season
     *
     * @param int $teamSeasonId Team Season ID
     * @return int|null Sport ID or null if not found
     */
    protected function getTeamSeasonSportId(int $teamSeasonId): ?int
    {
        /** @var \App\Model\Table\TeamSeasonsTable $teamSeasonsTable */
        $teamSeasonsTable = $this->fetchTable('TeamSeasons');

        $teamSeason = $teamSeasonsTable->find()
            ->contain(['Teams' => ['Sports']])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->first();

        if (!$teamSeason || !$teamSeason->team || !$teamSeason->team->sport) {
            return null;
        }

        return $teamSeason->team->sport->id;
    }

    /**
     * Check if a sport has statistical support
     *
     * @param int $sportId Sport ID
     * @return bool True if sport has a dedicated stats service
     */
    public function hasSportSupport(int $sportId): bool
    {
        $sportName = $this->sportConfig->getSportName($sportId);

        return isset($this->sportServiceMap[$sportName]);
    }

    /**
     * Get list of supported sport names
     *
     * @return array<string> List of sport names with stat support
     */
    public function getSupportedSports(): array
    {
        return array_keys($this->sportServiceMap);
    }

    /**
     * Search player season stats across sports.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchPlayerSeasonStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchPlayerSeasonStats')) {
            return [];
        }

        return $service->searchPlayerSeasonStats($filters);
    }

    /**
     * Search team season stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchTeamSeasonStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchTeamSeasonStats')) {
            return [];
        }

        return $service->searchTeamSeasonStats($filters);
    }

    /**
     * Search team season opponent stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchTeamSeasonOpponentStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchTeamSeasonOpponentStats')) {
            return [];
        }

        return $service->searchTeamSeasonOpponentStats($filters);
    }

    /**
     * Search player game stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchPlayerGameStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchPlayerGameStats')) {
            return [];
        }

        return $service->searchPlayerGameStats($filters);
    }

    /**
     * Search opponent player game stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchOpponentPlayerGameStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchOpponentPlayerGameStats')) {
            return [];
        }

        return $service->searchOpponentPlayerGameStats($filters);
    }

    /**
     * Search team game box score stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchTeamGameStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchTeamGameStats')) {
            return [];
        }

        return $service->searchTeamGameStats($filters);
    }

    /**
     * Search player career stats.
     *
     * @param int $sportId Sport ID
     * @param array $filters Search filters
     * @return array
     */
    public function searchPlayerCareerStats(int $sportId, array $filters = []): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'searchPlayerCareerStats')) {
            return [];
        }

        return $service->searchPlayerCareerStats($filters);
    }

    /**
     * Get filter options for a sport.
     *
     * @param int $sportId Sport ID
     * @return array{seasons: array, teams: array}
     */
    public function getFilterOptions(int $sportId): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getFilterOptions')) {
            return ['seasons' => [], 'teams' => []];
        }

        return $service->getFilterOptions();
    }

    /**
     * Get sport ID by sport name.
     *
     * @param string $sportName Sport name (case-insensitive)
     * @return int|null Sport ID or null if not found
     */
    public function getSportIdByName(string $sportName): ?int
    {
        /** @var \App\Model\Table\SportsTable $sportsTable */
        $sportsTable = $this->fetchTable('Sports');

        $sport = $sportsTable->find()
            ->where(['LOWER(sport_name)' => strtolower($sportName)])
            ->first();

        return $sport ? (int)$sport->id : null;
    }

    /**
     * Return ordered DataTables cell values for a player season stat.
     *
     * @param int $sportId
     * @param object $stat
     * @return array<int, int>
     */
    public function getPlayerSeasonStatCells(int $sportId, object $stat): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getPlayerSeasonStatCells')) {
            return [];
        }

        return $service->getPlayerSeasonStatCells($stat);
    }

    /**
     * Return ordered DataTables cell values for a team season stat.
     *
     * @param int $sportId
     * @param object $stat
     * @return array<int, int>
     */
    public function getTeamSeasonStatCells(int $sportId, object $stat): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getTeamSeasonStatCells')) {
            return [];
        }

        return $service->getTeamSeasonStatCells($stat);
    }

    /**
     * Return ordered DataTables cell values for player career totals.
     *
     * @param int $sportId
     * @param array<string, int> $totals
     * @return array<int, int>
     */
    public function getPlayerCareerStatCells(int $sportId, array $totals): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getPlayerCareerStatCells')) {
            return [];
        }

        return $service->getPlayerCareerStatCells($totals);
    }

    /**
     * Return ordered DataTables cell values for a player game stat.
     *
     * @param int $sportId
     * @param object $stat
     * @return array<int, int>
     */
    public function getPlayerGameStatCells(int $sportId, object $stat): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getPlayerGameStatCells')) {
            return [];
        }

        return $service->getPlayerGameStatCells($stat);
    }

    /**
     * Return ordered DataTables cell values for a team game box score stat.
     *
     * @param int $sportId
     * @param object $stat
     * @return array<int, int>
     */
    public function getTeamGameStatCells(int $sportId, object $stat): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getTeamGameStatCells')) {
            return [];
        }

        return $service->getTeamGameStatCells($stat);
    }

    /**
     * Return the opponent player name from an opponent game stat record.
     *
     * @param int $sportId
     * @param object $stat
     * @return string Raw (unescaped) name or empty string.
     */
    public function getOpponentPlayerName(int $sportId, object $stat): string
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getOpponentPlayerName')) {
            return '';
        }

        return $service->getOpponentPlayerName($stat);
    }

    /**
     * Return ordered DataTables cell values for an opponent player game stat.
     *
     * @param int $sportId
     * @param object $stat
     * @return array<int, int>
     */
    public function getOpponentPlayerGameStatCells(int $sportId, object $stat): array
    {
        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getOpponentPlayerGameStatCells')) {
            return [];
        }

        return $service->getOpponentPlayerGameStatCells($stat);
    }

    /**
     * Get player season stats list for the legacy season view.
     *
     * @param int $teamSeasonId
     * @return array
     */
    public function getSeasonPlayerStatsList(int $teamSeasonId): array
    {
        $sportId = $this->getTeamSeasonSportId($teamSeasonId);
        if (!$sportId) {
            return [];
        }

        $service = $this->getSportService($sportId);
        if (!$service || !method_exists($service, 'getSeasonPlayerStatsList')) {
            return [];
        }

        return $service->getSeasonPlayerStatsList($teamSeasonId);
    }
}
