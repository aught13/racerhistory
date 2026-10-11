<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * DropLegacySportSnapshots migration
 *
 * Remove the temporary snapshots used during Sports schema retirement.
 * Rolling this migration back restores the tables, but not their contents.
 */
class DropLegacySportSnapshots extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Drop the legacy snapshot tables.
     */
    public function up(): void
    {
        if ($this->hasTable('legacy_team_sport_snapshot')) {
            $this->table('legacy_team_sport_snapshot')->drop()->save();
        }

        if ($this->hasTable('legacy_sports_snapshot')) {
            $this->table('legacy_sports_snapshot')->drop()->save();
        }
    }

    /**
     * Restore the snapshot table schemas without their discarded contents.
     */
    public function down(): void
    {
        if (!$this->hasTable('legacy_sports_snapshot')) {
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

        if (!$this->hasTable('legacy_team_sport_snapshot')) {
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
    }
}
