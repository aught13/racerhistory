<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;
use Throwable;

/**
 * TeamSportContextService
 *
 * Transitional helper for resolving sport context from team records while both
 * legacy `sport_id` and canonical `sport_key` may coexist.
 */
class TeamSportContextService
{
    private SportConfigService $sportConfigService;
    private ?bool $teamsHasSportKeyColumn = null;
    private ?bool $teamsHasSportIdColumn = null;

    /**
     * @param \App\Service\SportConfigService|null $sportConfigService Sport config service
     */
    public function __construct(?SportConfigService $sportConfigService = null)
    {
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
    }

    /**
     * Build legacy-friendly select options keyed by sport_id.
     *
     * @return array<int,string>
     */
    public function getLegacySportOptions(): array
    {
        $available = $this->sportConfigService->getAvailableSports();
        $options = [];

        foreach ($available as $sportKey => $displayName) {
            $sportId = $this->sportConfigService->getIdByKey((string)$sportKey);
            if ($sportId === null || $sportId <= 0) {
                continue;
            }
            $options[(int)$sportId] = (string)$displayName;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * Build canonical select options keyed by sport_key.
     *
     * @return array<string,string>
     */
    public function getSportOptions(): array
    {
        $options = $this->sportConfigService->getAvailableSports();
        uasort($options, static fn(string $a, string $b): int => strcasecmp($a, $b));

        return $options;
    }

    /**
     * Resolve canonical sport key from a team-like object.
     *
     * @param object|null $team Team entity or similar object
     * @return string|null
     */
    public function resolveSportKeyFromTeam(?object $team): ?string
    {
        if ($team === null) {
            return null;
        }

        $rawKey = trim((string)($team->sport_key ?? ''));
        if ($rawKey !== '') {
            return strtolower($rawKey);
        }

        $sportId = (int)($team->sport_id ?? 0);
        if ($sportId > 0) {
            return $this->sportConfigService->getKeyById($sportId);
        }

        $legacySport = $team->sport ?? null;
        if (is_object($legacySport)) {
            $legacySportId = (int)($legacySport->id ?? 0);
            if ($legacySportId > 0) {
                return $this->sportConfigService->getKeyById($legacySportId);
            }

            $legacySportName = trim((string)($legacySport->sport_name ?? ''));
            if ($legacySportName !== '') {
                return $this->resolveSportKey($legacySportName);
            }
        }

        return null;
    }

    /**
     * Resolve legacy numeric sport id from a team-like object.
     *
     * @param object|null $team Team entity or similar object
     * @return int|null
     */
    public function resolveSportIdFromTeam(?object $team): ?int
    {
        if ($team === null) {
            return null;
        }

        $sportId = (int)($team->sport_id ?? 0);
        if ($sportId > 0) {
            return $sportId;
        }

        $legacySport = $team->sport ?? null;
        if (is_object($legacySport)) {
            $legacySportId = (int)($legacySport->id ?? 0);
            if ($legacySportId > 0) {
                return $legacySportId;
            }

            $legacySportName = trim((string)($legacySport->sport_name ?? ''));
            if ($legacySportName !== '') {
                $legacySportKey = $this->resolveSportKey($legacySportName);
                if ($legacySportKey !== null) {
                    return $this->sportConfigService->getIdByKey($legacySportKey);
                }
            }
        }

        $sportKey = $this->resolveSportKeyFromTeam($team);
        if ($sportKey === null) {
            return null;
        }

        return $this->sportConfigService->getIdByKey($sportKey);
    }

    /**
     * Resolve display name from a team-like object.
     *
     * @param object|null $team Team entity or similar object
     * @return string|null
     */
    public function resolveSportNameFromTeam(?object $team): ?string
    {
        $sportKey = $this->resolveSportKeyFromTeam($team);
        if ($sportKey === null) {
            return null;
        }

        return $this->sportConfigService->getSportDisplayName($sportKey);
    }

    /**
     * Resolve canonical sport key from legacy sport id.
     *
     * @param int $sportId
     * @return string|null
     */
    public function resolveSportKeyFromId(int $sportId): ?string
    {
        if ($sportId <= 0) {
            return null;
        }

        return $this->sportConfigService->getKeyById($sportId);
    }

    /**
     * Resolve legacy sport id from canonical key.
     *
     * @param string $sportKey
     * @return int|null
     */
    public function resolveSportIdFromKey(string $sportKey): ?int
    {
        $normalizedKey = trim(strtolower($sportKey));
        if ($normalizedKey === '') {
            return null;
        }

        return $this->sportConfigService->getIdByKey($normalizedKey);
    }

    /**
     * Attach compatibility sport fields onto a team entity-like object.
     *
     * Adds:
     * - sport_key
     * - sport_name
     * - sport object with id and sport_name properties
     *
     * @param object|null $team Team entity or similar object
     * @return void
     */
    public function attachSportContextToTeam(?object $team): void
    {
        if ($team === null || !method_exists($team, 'set')) {
            return;
        }

        $sportKey = $this->resolveSportKeyFromTeam($team);
        if ($sportKey === null) {
            return;
        }

        $sportId = $this->sportConfigService->getIdByKey($sportKey);
        $sportName = $this->sportConfigService->getSportDisplayName($sportKey);

        $team->set('sport_key', $sportKey);
        if (empty($team->sport_id) && $sportId !== null) {
            $team->set('sport_id', $sportId);
        }

        $sport = (object)[
            'id' => $sportId,
            'sport_name' => $sportName,
        ];

        $team->set('sport', $sport);
        $team->set('sport_name', $sportName);
    }

    /**
     * Resolve a sport key from user-facing sport text.
     *
     * @param string $sportLabel Sport key or display label
     * @return string|null
     */
    public function resolveSportKey(string $sportLabel): ?string
    {
        $normalized = $this->normalizeSportLabel($sportLabel);
        if ($normalized === '') {
            return null;
        }

        $available = $this->sportConfigService->getAvailableSports();
        foreach ($available as $sportKey => $displayName) {
            if ($normalized === $this->normalizeSportLabel((string)$sportKey)) {
                return (string)$sportKey;
            }

            if ($normalized === $this->normalizeSportLabel((string)$displayName)) {
                return (string)$sportKey;
            }
        }

        return null;
    }

    /**
     * Build OR-based filter conditions that support sport_key with sport_id
     * fallback for pre-migration rows.
     *
     * @param string $sportKey Canonical sport key
     * @param string $alias Table alias that owns sport columns
     * @return array<string,mixed>
     */
    public function buildSportFilterConditions(string $sportKey, string $alias = 'Teams'): array
    {
        $normalizedKey = trim(strtolower($sportKey));
        if ($normalizedKey === '') {
            return [];
        }

        $keyColumn = $alias . '.sport_key';
        $idColumn = $alias . '.sport_id';
        $primaryColumn = $alias . '.id';
        $sportId = $this->sportConfigService->getIdByKey($normalizedKey);
        $hasSportKeyColumn = $this->supportsSportKeyColumn($alias);
        $hasSportIdColumn = $this->supportsSportIdColumn($alias);

        if (!$hasSportKeyColumn && !$hasSportIdColumn) {
            return [$primaryColumn => -1];
        }

        if (!$hasSportKeyColumn) {
            if ($sportId === null) {
                return [$idColumn => -1];
            }

            return [$idColumn => $sportId];
        }

        if (!$hasSportIdColumn) {
            return [$keyColumn => $normalizedKey];
        }

        if ($sportId === null) {
            return [$keyColumn => $normalizedKey];
        }

        return [
            'OR' => [
                [$keyColumn => $normalizedKey],
                [$keyColumn . ' IS' => null, $idColumn => $sportId],
            ],
        ];
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeSportLabel(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, "men's ")) {
            $normalized = substr($normalized, 6);
        } elseif (str_starts_with($normalized, "women's ")) {
            $normalized = substr($normalized, 8);
        } elseif (str_starts_with($normalized, 'mens ')) {
            $normalized = substr($normalized, 5);
        } elseif (str_starts_with($normalized, 'womens ')) {
            $normalized = substr($normalized, 7);
        }

        return trim($normalized);
    }

    /**
     * Determine if we can safely reference the sport_key column for a table alias.
     *
     * @param string $alias
     * @return bool
     */
    private function supportsSportKeyColumn(string $alias): bool
    {
        if ($alias !== 'Teams') {
            return true;
        }

        if ($this->teamsHasSportKeyColumn !== null) {
            return $this->teamsHasSportKeyColumn;
        }

        try {
            $teamsTable = TableRegistry::getTableLocator()->get('Teams');
            $this->teamsHasSportKeyColumn = $teamsTable->getSchema()->hasColumn('sport_key');
        } catch (Throwable) {
            $this->teamsHasSportKeyColumn = false;
        }

        return $this->teamsHasSportKeyColumn;
    }

    /**
     * Determine if we can safely reference the sport_id column for a table alias.
     *
     * @param string $alias
     * @return bool
     */
    private function supportsSportIdColumn(string $alias): bool
    {
        if ($alias !== 'Teams') {
            return true;
        }

        if ($this->teamsHasSportIdColumn !== null) {
            return $this->teamsHasSportIdColumn;
        }

        try {
            $teamsTable = TableRegistry::getTableLocator()->get('Teams');
            $this->teamsHasSportIdColumn = $teamsTable->getSchema()->hasColumn('sport_id');
        } catch (Throwable) {
            $this->teamsHasSportIdColumn = false;
        }

        return $this->teamsHasSportIdColumn;
    }
}
