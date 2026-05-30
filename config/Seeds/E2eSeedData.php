<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * E2E seed data for Playwright tests.
 *
 * Inserts a minimal set of records so that /games/view/1 and /people/view/1
 * return HTTP 200 and render their Turbo Frames in CI (where the DB is
 * otherwise empty after migrations). All IDs are forced to 1 so that the
 * hard-coded URLs in the E2E specs work without modification.
 */
class E2eSeedData extends BaseSeed
{
    /**
     * Check if a table has a specific column.
     */
    private function hasColumn(string $table, string $column): bool
    {
        return $this->getAdapter()->hasColumn($table, $column);
    }

    /**
     * Insert a row with idempotent behavior.
     *
     * @param array<string, int|string> $row
     */
    private function upsert(string $table, array $row): void
    {
        $columns = array_keys($row);
        $quotedColumns = array_map(
            static fn (string $column): string => sprintf('`%s`', $column),
            $columns,
        );
        $placeholders = array_fill(0, count($columns), '?');

        $this->execute(
            sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE `%s` = `%s`',
                $table,
                implode(', ', $quotedColumns),
                implode(', ', $placeholders),
                $columns[0],
                $columns[0],
            ),
            array_values($row),
        );
    }

    /**
     * Run seed data insertions.
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // 0. E2E admin user (required for Playwright tests that authenticate)
        $this->upsert('users', [
            'id' => 1,
            'username' => 'e2e',
            'email' => 'e2e@example.com',
            'password' => '$2y$12$mkoyJKr/PZpRr0Mua8HKHuGyAj6QA3TQrwF8VvtdCOi/vme55jITy',
            'role' => 'admin',
            'status' => 'active',
            'active' => 1,
            'is_superuser' => 1,
            'created' => $now,
            'modified' => $now,
        ]);

        // 1. Place (required by sites and opponents)
        $place = [
            'id' => 1,
            'place_country' => 'USA',
            'place_state' => 'TX',
        ];
        if ($this->hasColumn('places', 'place_city')) {
            $place['place_city'] = 'Murray';
        }
        $this->upsert('places', $place);

        // 2. Sport
        $this->upsert('sports', [
            'id' => 1,
            'sport_name' => 'Basketball',
        ]);

        // 3. Team (references sport)
        $team = [
            'id' => 1,
            'sport_id' => 1,
            'team_name' => 'Test Racers',
            'abbr' => 'TRH',
            'gender' => 'M',
        ];
        if ($this->hasColumn('teams', 'team_nickname')) {
            $team['team_nickname'] = 'Racers';
        }
        if ($this->hasColumn('teams', 'team_scorebug')) {
            $team['team_scorebug'] = 'RACER';
        }
        $this->upsert('teams', $team);

        // 4. Season
        $this->upsert('seasons', [
            'id' => 1,
            'start' => '2024',
            'end' => '2025',
            'created_at' => $now,
        ]);

        // 5. TeamSeason (ties a team to a season)
        $this->upsert('team_season', [
            'id' => 1,
            'team_id' => 1,
            'season_id' => 1,
            'semester' => 2,
        ]);

        // 6. Opponent (for games table FK)
        $this->upsert('opponents', [
            'id' => 1,
            'opponent_name' => 'Test Opponent',
            'opponent_abbr' => 'OPP',
            'place_id' => 1,
        ]);

        // 7. Game (id=1 so /games/view/1 works)
        $this->upsert('games', [
            'id' => 1,
            'team_season_id' => 1,
            'game_date' => '2024-12-01',
            'hrn' => 0,
            'w' => '1',
            'l' => '0',
        ]);

        // 8. Person (id=1 so /people/view/1 works)
        $this->upsert('persons', [
            'id' => 1,
            'first' => 'Test',
            'last' => 'Person',
            'full' => 'Test Person',
            'display' => 'T. Person',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 9. Roster entry (links the person to the team season so the
        // game-log Turbo Frame tabs appear on /people/view/1)
        $this->upsert('team_season_roster', [
            'id' => 1,
            'team_season_id' => 1,
            'person_id' => 1,
        ]);
    }
}
