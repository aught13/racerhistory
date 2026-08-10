<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * RemoveSportsTable migration
 *
 * Stage 3 of Sports schema retirement:
 * - Snapshot legacy sports rows.
 * - Drop legacy `sports` table.
 *
 * Down migration recreates and repopulates `sports` from snapshot data.
 */
class RemoveSportsTable extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Snapshot and remove legacy sports table.
     */
    public function up(): void
    {
        $this->ensureSportsSnapshotTable();
        $this->snapshotSportsTable();

        if ($this->hasTable('sports')) {
            $this->table('sports')->drop()->save();
        }
    }

    /**
     * Recreate and repopulate sports table from snapshots.
     */
    public function down(): void
    {
        if (!$this->hasTable('sports')) {
            $this->table('sports')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'null' => false,
                    'signed' => false,
                ])
                ->addPrimaryKey(['id'])
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
                ->addIndex(['sport_name'], [
                    'name' => 'name',
                    'unique' => true,
                ])
                ->create();
        }

        if (!$this->hasTable('legacy_sports_snapshot')) {
            return;
        }

        $rows = $this->fetchAll(
            'SELECT id, sport_name, created_at, updated_at FROM legacy_sports_snapshot ORDER BY id',
        );
        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $sportName = (string)($row['sport_name'] ?? '');
            if ($id <= 0 || $sportName === '') {
                continue;
            }

            $createdAt = $row['created_at'] ?? null;
            $updatedAt = $row['updated_at'] ?? null;

            $existing = $this->fetchRow('SELECT id FROM sports WHERE id = ' . $id);
            if ($existing) {
                continue;
            }

            $this->table('sports')->insert([
                'id' => $id,
                'sport_name' => $sportName,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ])->save();
        }
    }

    /**
     * Ensure snapshot table exists for sports rows.
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
     * Snapshot sports rows if not already backed up.
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
}
