<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * PrepareSportRetirementSnapshots migration
 *
 * Stage 1 of Sports schema retirement:
 * - Snapshot legacy `sports` and `teams.sport_id` data for safe rollback.
 * - Ensure `teams.sport_key` is populated for legacy rows.
 * - Keep runtime schema backward compatible (non-destructive stage).
 */
class PrepareSportRetirementSnapshots extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Create snapshots and backfill canonical team sport keys.
     */
    public function up(): void
    {
        $this->ensureSportsSnapshotTable();
        $this->ensureTeamSportSnapshotTable();

        $this->snapshotSportsTable();
        $this->snapshotTeamSportData();

        $this->backfillTeamSportKeys();
        $this->ensureTeamSportKeyIndex();
    }

    /**
     * Keep snapshots in place to support downstream rollback paths.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
        // Snapshots are retained to keep downstream rollback paths safe.
    }

    /**
     * Ensure snapshot table exists for legacy sports rows.
     */
    private function ensureSportsSnapshotTable(): void
    {
        if ($this->hasTable('legacy_sports_snapshot')) {
            return;
        }

        $this->table('legacy_sports_snapshot', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('sport_name', 'string', [
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
            ])
            ->create();
    }

    /**
     * Ensure snapshot table exists for team sport mappings.
     */
    private function ensureTeamSportSnapshotTable(): void
    {
        if ($this->hasTable('legacy_team_sport_snapshot')) {
            return;
        }

        $this->table('legacy_team_sport_snapshot', ['id' => false, 'primary_key' => ['team_id']])
            ->addColumn('team_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('sport_id', 'integer', [
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('sport_key', 'string', [
                'limit' => 64,
                'null' => true,
            ])
            ->create();
    }

    /**
     * Snapshot legacy sports table rows once.
     */
    private function snapshotSportsTable(): void
    {
        if (!$this->hasTable('sports') || !$this->hasTable('legacy_sports_snapshot')) {
            return;
        }

        $this->execute(
            "INSERT INTO legacy_sports_snapshot (id, sport_name, created_at, updated_at)
            SELECT s.id, s.sport_name, s.created_at, s.updated_at
            FROM sports s
            LEFT JOIN legacy_sports_snapshot b ON b.id = s.id
            WHERE b.id IS NULL",
        );
    }

    /**
     * Snapshot team -> sport_id/sport_key values.
     */
    private function snapshotTeamSportData(): void
    {
        if (!$this->hasTable('teams') || !$this->hasTable('legacy_team_sport_snapshot')) {
            return;
        }

        $teams = $this->table('teams');
        $hasSportId = $teams->hasColumn('sport_id');
        $hasSportKey = $teams->hasColumn('sport_key');

        if (!$hasSportId && !$hasSportKey) {
            return;
        }

        $sportIdColumn = $hasSportId ? 't.sport_id' : 'NULL';
        $sportKeyColumn = $hasSportKey ? 't.sport_key' : 'NULL';

        $this->execute(
            "INSERT INTO legacy_team_sport_snapshot (team_id, sport_id, sport_key)
            SELECT t.id, {$sportIdColumn}, {$sportKeyColumn}
            FROM teams t
            LEFT JOIN legacy_team_sport_snapshot b ON b.team_id = t.id
            WHERE b.team_id IS NULL",
        );
    }

    /**
     * Backfill missing teams.sport_key values from deterministic mappings/snapshots.
     */
    private function backfillTeamSportKeys(): void
    {
        if (!$this->hasTable('teams')) {
            return;
        }

        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_key')) {
            return;
        }

        $sportNamesById = [];
        if ($this->hasTable('legacy_sports_snapshot')) {
            $rows = $this->fetchAll('SELECT id, sport_name FROM legacy_sports_snapshot');
            foreach ($rows as $row) {
                $sportId = (int)($row['id'] ?? 0);
                $sportName = trim((string)($row['sport_name'] ?? ''));
                if ($sportId <= 0 || $sportName === '') {
                    continue;
                }
                $sportNamesById[$sportId] = $sportName;
            }
        }

        $selectColumns = $teams->hasColumn('sport_id')
            ? 'id, sport_id, sport_key'
            : 'id, sport_key';

        $teamRows = $this->fetchAll('SELECT ' . $selectColumns . ' FROM teams');
        foreach ($teamRows as $teamRow) {
            $teamId = (int)($teamRow['id'] ?? 0);
            if ($teamId <= 0) {
                continue;
            }

            $currentKey = trim((string)($teamRow['sport_key'] ?? ''));
            if ($currentKey !== '') {
                continue;
            }

            $sportId = (int)($teamRow['sport_id'] ?? 0);
            $resolvedKey = match ($sportId) {
                1 => 'basketball',
                2 => 'football',
                3 => 'baseball',
                default => null,
            };

            if ($resolvedKey === null && isset($sportNamesById[$sportId])) {
                $resolvedKey = $this->normalizeLegacySportName($sportNamesById[$sportId]);
            }

            if ($resolvedKey === null || $resolvedKey === '') {
                continue;
            }

            $this->execute(
                "UPDATE teams
                SET sport_key = '" . addslashes($resolvedKey) . "'
                WHERE id = " . $teamId,
            );
        }

        // Keep snapshot current after key backfill.
        if ($this->hasTable('legacy_team_sport_snapshot')) {
            $updatedRows = $this->fetchAll(
                "SELECT id, sport_key FROM teams WHERE sport_key IS NOT NULL AND sport_key <> ''",
            );
            foreach ($updatedRows as $updatedRow) {
                $teamId = (int)($updatedRow['id'] ?? 0);
                $sportKey = trim((string)($updatedRow['sport_key'] ?? ''));
                if ($teamId <= 0 || $sportKey === '') {
                    continue;
                }

                $this->execute(
                    "UPDATE legacy_team_sport_snapshot
                    SET sport_key = '" . addslashes($sportKey) . "'
                    WHERE team_id = " . $teamId . "
                      AND (sport_key IS NULL OR sport_key = '')",
                );
            }
        }
    }

    /**
     * Ensure teams.sport_key has an index for lookup performance.
     */
    private function ensureTeamSportKeyIndex(): void
    {
        if (!$this->hasTable('teams')) {
            return;
        }

        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_key') || $teams->hasIndex(['sport_key'])) {
            return;
        }

        $teams->addIndex(['sport_key'], ['name' => 'idx_teams_sport_key'])->update();
    }

    /**
     * Convert human sport names into canonical key-like labels.
     *
     * @param string $sportName
     * @return string
     */
    private function normalizeLegacySportName(string $sportName): string
    {
        $normalized = strtolower(trim($sportName));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }
}
