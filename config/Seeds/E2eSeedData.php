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
        $teamSeasonRosterId = 1;
        $this->upsert('team_season_roster', [
            'id' => $teamSeasonRosterId,
            'team_season_id' => 1,
            'person_id' => 1,
        ]);

        // 10. Image (required for admin-images-loading tests to pass)
        // Use a minimal valid PNG (1x1 pixel, transparent) as base64
        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );
        $this->upsert('images', [
            'id' => 1,
            'filename' => 'test-image.png',
            'storage_path' => '2024/12/test-image.png',
            'original_name' => 'test-image.png',
            'mime' => 'image/png',
            'ext' => 'png',
            'byte_size' => strlen($pngData),
            'width' => 1,
            'height' => 1,
            'hash' => hash('sha256', $pngData),
            'status' => 'active',
            'created' => $now,
            'modified' => $now,
        ]);

        // 11. Game box stats (required for search-builder stats table tests)
        // This is the team's box score for the game
        $this->upsert('stat_basket_game_box', [
            'id' => 1,
            'game_id' => 1,
            'opponent_id' => null,  // null = team stats (not opponent)
            'period' => 'full',
            'FGM' => '35',
            'FGA' => '70',
            'TPM' => '8',
            'TPA' => '20',
            'FTM' => '12',
            'FTA' => '15',
            'ORB' => '10',
            'DRB' => '25',
            'RB' => '35',
            'AST' => '18',
            'STL' => '6',
            'BS' => '3',
            'TRN' => '8',
            'PF' => '16',
            'TF' => '0',
            'PTS' => '90',
            'PNT' => '0',
            'OTO' => '0',
            'SND' => '0',
            'FB' => '5',
            'BN' => '0',
            'TIED' => '0',
            'LC' => '0',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 12. Opponent box stats (for comparison in stats tables)
        $this->upsert('stat_basket_game_box', [
            'id' => 2,
            'game_id' => 1,
            'opponent_id' => 1,  // opponent_id = opponent stats
            'period' => 'full',
            'FGM' => '28',
            'FGA' => '65',
            'TPM' => '5',
            'TPA' => '18',
            'FTM' => '8',
            'FTA' => '12',
            'ORB' => '8',
            'DRB' => '20',
            'RB' => '28',
            'AST' => '14',
            'STL' => '5',
            'BS' => '2',
            'TRN' => '10',
            'PF' => '14',
            'TF' => '0',
            'PTS' => '69',
            'PNT' => '0',
            'OTO' => '0',
            'SND' => '0',
            'FB' => '3',
            'BN' => '0',
            'TIED' => '0',
            'LC' => '0',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 13. Player game stats (required for player stats tables to display)
        // Player (person_id=1) game stats for the game
        $this->upsert('stat_basket_game_person', [
            'id' => 1,
            'team_season_roster_id' => $teamSeasonRosterId,
            'game_id' => 1,
            'period' => 'full',
            'GP' => '1',
            'GS' => '1',
            'MIN' => '32',
            'FGM' => '8',
            'FGA' => '18',
            'TPM' => '2',
            'TPA' => '6',
            'FTM' => '3',
            'FTA' => '4',
            'ORB' => '2',
            'DRB' => '5',
            'RB' => '7',
            'AST' => '4',
            'STL' => '1',
            'BS' => '0',
            'TRN' => '2',
            'PF' => '3',
            'PTS' => '21',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
