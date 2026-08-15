<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * RemoveSportIdFromTeams migration
 *
 * Stage 2 of Sports schema retirement:
 * - Persist last-known `teams.sport_id` values into snapshot storage.
 * - Remove legacy `teams.sport_id`.
 *
 * Down migration restores `teams.sport_id` from snapshot data.
 */
class RemoveSportIdFromTeams extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Snapshot and remove teams.sport_id.
     */
    public function up(): void
    {
        if (!$this->hasTable('teams')) {
            return;
        }

        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_id')) {
            return;
        }

        $this->ensureTeamSportSnapshotTable();
        $this->snapshotTeamSportData();
        $this->dropTeamSportIdForeignKey();

        $teams->removeColumn('sport_id')->update();
    }

    /**
     * Restore teams.sport_id from snapshot data and fallback mapping.
     */
    public function down(): void
    {
        if (!$this->hasTable('teams')) {
            return;
        }

        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_id')) {
            $teams->addColumn('sport_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'sport_key',
            ])->update();
        }

        if ($this->hasTable('legacy_team_sport_snapshot')) {
            $rows = $this->fetchAll(
                'SELECT team_id, sport_id FROM legacy_team_sport_snapshot WHERE sport_id IS NOT NULL',
            );
            foreach ($rows as $row) {
                $teamId = (int)($row['team_id'] ?? 0);
                $sportId = (int)($row['sport_id'] ?? 0);
                if ($teamId <= 0 || $sportId <= 0) {
                    continue;
                }

                $this->execute(
                    "UPDATE teams
                    SET sport_id = " . $sportId . "
                    WHERE id = " . $teamId . "
                      AND (sport_id IS NULL OR sport_id = 0)",
                );
            }
        }

        // Fallback mapping from canonical key when snapshot is missing or incomplete.
        $this->execute(
            "UPDATE teams
            SET sport_id = CASE sport_key
                WHEN 'basketball' THEN 1
                WHEN 'football' THEN 2
                WHEN 'baseball' THEN 3
                ELSE sport_id
            END
            WHERE sport_id IS NULL OR sport_id = 0",
        );

        $this->restoreTeamSportIdForeignKey();
    }

    /**
     * Drop sport_id foreign key from teams before dropping the column.
     */
    private function dropTeamSportIdForeignKey(): void
    {
        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_id')) {
            return;
        }

        if (!$teams->hasForeignKey('sport_id')) {
            return;
        }

        $teams->dropForeignKey('sport_id')->update();
    }

    /**
     * Restore index and foreign key for teams.sport_id when rolling back.
     */
    private function restoreTeamSportIdForeignKey(): void
    {
        if (!$this->hasTable('sports')) {
            return;
        }

        $teams = $this->table('teams');
        if (!$teams->hasColumn('sport_id')) {
            return;
        }

        if (!$teams->hasIndex(['sport_id'])) {
            $teams->addIndex(['sport_id'], ['name' => 'sport_id'])->update();
        }

        if ($teams->hasForeignKey('sport_id')) {
            return;
        }

        $teams->addForeignKey('sport_id', 'sports', 'id', [
            'delete' => 'NO_ACTION',
            'update' => 'NO_ACTION',
        ])->update();
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
     * Capture current team sport mappings before schema drop.
     */
    private function snapshotTeamSportData(): void
    {
        if (!$this->hasTable('legacy_team_sport_snapshot')) {
            return;
        }

        $teams = $this->table('teams');
        $hasSportKey = $teams->hasColumn('sport_key');

        $sportKeyColumn = $hasSportKey ? 't.sport_key' : 'NULL';

        $this->execute(
            "INSERT INTO legacy_team_sport_snapshot (team_id, sport_id, sport_key)
            SELECT t.id, t.sport_id, {$sportKeyColumn}
            FROM teams t
            LEFT JOIN legacy_team_sport_snapshot b ON b.team_id = t.id
            WHERE b.team_id IS NULL",
        );

        if ($hasSportKey) {
            $rows = $this->fetchAll(
                "SELECT id, sport_key FROM teams WHERE sport_key IS NOT NULL AND sport_key <> ''",
            );
            foreach ($rows as $row) {
                $teamId = (int)($row['id'] ?? 0);
                $sportKey = trim((string)($row['sport_key'] ?? ''));
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
}
