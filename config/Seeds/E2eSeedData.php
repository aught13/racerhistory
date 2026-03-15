<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * E2E seed data for Playwright tests.
 *
 * Inserts a minimal set of records so that /games/view/1 and /people/view/1
 * return HTTP 200 and render their Turbo Frames in CI (where the DB is
 * otherwise empty after migrations).  All IDs are forced to 1 so that the
 * hard-coded URLs in the E2E specs work without modification.
 */
class E2eSeedData extends AbstractSeed
{
    /**
     * Run seed data insertions.
     *
     * @return void
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // 1. Place (required by sites and opponents)
        $this->execute("
            INSERT INTO places (id, place_name, place_city, place_state)
            VALUES (1, 'Test City', 'Test City', 'TX')
            ON DUPLICATE KEY UPDATE place_name = place_name
        ");

        // 2. Sport
        $this->execute("
            INSERT INTO sports (id, sport_name)
            VALUES (1, 'Basketball')
            ON DUPLICATE KEY UPDATE sport_name = sport_name
        ");

        // 3. Team (references sport)
        $this->execute("
            INSERT INTO teams (id, sport_id, team_name, team_nickname, team_scorebug, abbr, gender)
            VALUES (1, 1, 'Test Racers', 'Racers', 'RACER', 'TRH', 'M')
            ON DUPLICATE KEY UPDATE team_name = team_name
        ");

        // 4. Season
        $this->execute("
            INSERT INTO seasons (id, start, end, created_at)
            VALUES (1, '2024', '2025', '{$now}')
            ON DUPLICATE KEY UPDATE start = start
        ");

        // 5. TeamSeason (ties a team to a season)
        $this->execute("
            INSERT INTO team_season (id, team_id, season_id, semester)
            VALUES (1, 1, 1, 2)
            ON DUPLICATE KEY UPDATE team_id = team_id
        ");

        // 6. Opponent (for games table FK)
        $this->execute("
            INSERT INTO opponents (id, opponent_name, opponent_abbr, place_id)
            VALUES (1, 'Test Opponent', 'OPP', 1)
            ON DUPLICATE KEY UPDATE opponent_name = opponent_name
        ");

        // 7. Game (id=1 so /games/view/1 works)
        $this->execute("
            INSERT INTO games (id, team_season_id, game_date, hrn, w, l)
            VALUES (1, 1, '2024-12-01', 0, '1', '0')
            ON DUPLICATE KEY UPDATE team_season_id = team_season_id
        ");

        // 8. Person (id=1 so /people/view/1 works)
        $this->execute("
            INSERT INTO persons (id, first, last, full, display, created_at, updated_at)
            VALUES (1, 'Test', 'Person', 'Test Person', 'T. Person', '{$now}', '{$now}')
            ON DUPLICATE KEY UPDATE first = first
        ");

        // 9. Roster entry (links the person to the team season so the
        //    game-log Turbo Frame tabs appear on /people/view/1)
        $this->execute("
            INSERT INTO team_season_roster (id, team_season_id, person_id)
            VALUES (1, 1, 1)
            ON DUPLICATE KEY UPDATE team_season_id = team_season_id
        ");
    }
}
