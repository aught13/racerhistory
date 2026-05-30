# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres (at the moment) to semantic versioning *starting with pre-release identifiers*.

## [Unreleased]

### Changed

- **Minimum PHP version raised to 8.2+** across project requirements and docs to match the current dependency graph.
- **CI testsuite matrix updated** to remove the incompatible PHP 8.1/lowest-dependencies lane and run coverage on PHP 8.2.
- **Deployment asset audits modernized** to require Vite build artifacts (`webroot/dist/manifest.json`) and validate the `js/main.js` manifest entry.

### Fixed

- **Admin users add/edit failure handling** now preserves validation context and avoids invalid redirects on save failures.
- **Dashboard layout tests** updated for the Vite runtime contract (hashed dist asset or dev entry) and legacy importmap removal.
- **Playwright stabilization for image/admin dynamic pages** by asserting runtime behavior instead of legacy global helper existence.

### Removed

- Legacy image-page JavaScript compatibility globals (`window.resetCrop`, `window.setRotation`, `window.setAspectRatio`, `window.resetAll`).
- Legacy `webroot/js/hotwire/application.js` fallback runtime entry from deployment-critical paths.
- Obsolete JavaScript coverage/debug tests that targeted removed runtime paths.

## [0.2.0-beta] - 2026-03-14

First beta release — application is feature-complete for initial production deployment.

### Added - Basketball Stats Integration

- **Season Totals Update Option**: "Update Season Totals" checkbox on Basketball Game Box final stats form (period Z) enabling direct aggregation of game box totals into season team &amp; opponent totals.
- **Service Layer Introduction**: New `BasketballStatsService` centralizes loading of basketball game statistics (team box, opponent box, periods, player stats, opponent player stats, team unattributed stats) for `GamesController::view()`.
- **Dedicated Controller**: New `Admin\StatBasketGameBoxController` handling game box (final) and per-period basketball stats management, reducing responsibilities in `Admin\GamesController`.

### Changed - Architecture & Separation of Concerns

- `Admin\GamesController` refactored to a minimal, sport-aware orchestrator: now only verifies basketball stats existence and delegates data assembly to the service layer.
- Basketball game stats UI controls reorganized into a single, clearer action group; opponent labels clarified; button routes updated to use the new controller.
- Improved maintainability by isolating season totals update logic inside `StatBasketGameBoxController::updateSeasonTotals()` with explicit handling for edits.

### Fixed - Basketball Stats

- Corrected opponent season totals persistence: replaced invalid lookup on non-existent `opponent_id` with proper `team_season_id` usage.
- Ensured hidden `game_id` propagation in player stat forms to fix prior save failures.
- Resolved PHPStan and PHPCS issues introduced during refactor.

### Added - Frontend & Hotwire

- Hotwire Turbo 8.x integration for SPA-like navigation without full page reloads.
- Hotwire Stimulus 3.x for progressive enhancement.
- ES module loaders for page-specific JavaScript initialization (DataTables, charts, search).
- All page loads initialize on both `DOMContentLoaded` and `turbo:load` events.
- Extracted shared admin JavaScript into `webroot/js/admin.js` and `webroot/js/admin.mjs`.

### Added - Image Pipeline

- Public image storage: `webroot/img/storage/{YYYY}/{MM}/UUID.ext` with JSON responses including `url` and `direct_url`.
- Image upload endpoints, `ImageProcessor` and variant generation (thumb/medium/WebP).
- Admin image browse/select UI with tagging and crop support.
- Reusable `element/image_with_fallback.php` for robust image rendering.

### Added - Blog Engine

- Public blog routes (`/blog`, `/blog/{slug}`) with published-only listing.
- Admin blog editor (`/admin/blog-posts`) with draft/publish workflow.
- TinyMCE integration with inline image uploads.
- Blog tagging reusing the same tag infrastructure as images.

### Added - Game Management System

- Smart sport-aware dynamic forms with AJAX loading of period/official fields.
- Business rule validation (season date ranges, future game restrictions, cumulative scoring).
- Multi-sport support: Basketball (halves/quarters), Football, Baseball.
- EAV attribute system for sport-specific game data (period scores, officials, attendance).

### Added - Basketball Season Statistics

- Complete CRUD for basketball team season statistics (player, team, opponent).
- Three new controllers: `StatBasketSeasonPersonController`, `StatBasketSeasonTeamController`, `StatBasketSeasonOpponentController`.
- Dynamic column filtering in stats display (hides columns with no data).
- jQuery DataTable integration with sorting, searching, and responsive design.

### Added - Basketball Game Statistics

- Complete CRUD for per-game box scores (player, opponent, team stats).
- Three new controllers: `StatBasketGamePersonController`, `StatBasketGameOpponentController`, `StatBasketGameTeamController`.
- Proper entity associations with team roster linkage.

### Added - Admin Management

- Sports management: add/edit/delete/bulk with configurable sport settings.
- Teams management: add/edit/delete/bulk with sport associations and gender classification.
- Seasons & Team Seasons management: rich text preview/recap with TinyMCE, image upload.
- Team Season Rosters management: add/edit/delete/bulk with inline DataTable and person search.
- Games management: add/edit/delete/bulk with sport-aware period/official tracking.
- Dynamic person AJAX search and inline person creation modal.
- Admin user management: approve/edit users, roles, first/last name support.

### Added - Production Tooling

- `bin/deploy.sh` production deployment script with auditing, migrations, and security checks.
- `bin/fix-permissions.sh` for file ownership and permission management.
- Jest + jsdom test suite (789 tests across 110 suites) with 91% statement coverage.
- PHPUnit test suite expanded to 983 tests / 2799 assertions.
- CI workflow: GitHub Actions for multi-PHP matrix, coverage upload to Codecov, static analysis.

### Changed - Code Quality

- Removed `console.log()` from production JavaScript; converted to `console.debug()`.
- Removed stale debug artifacts (`debug_service.php`, `psalm.xml`, `tests/manual_sport_test.php`).
- Updated `.gitignore` to exclude development-only status files.
- Cleaned stale TODO comments from templates.
- All quality gates passing: PHPStan (0 errors), PHPCS (clean), ESLint (clean), Prettier (clean).

### Security

- CSRF and FormProtection enabled globally.
- Authorization policies for request-level and entity-level checks.
- CDN integrity verification for external resources.
- Debug mode controlled via environment variable (defaults to false for production).

## [0.1.9-alpha] - 2025-12-30

### Added

- **Blog engine**: public blog routes (`/blog`, `/blog/{slug}`) with published-only listing.
- **Admin blog editor**: `/admin/blog-posts` add/edit/delete with draft/publish workflow.
- **Blog tagging + hero images**: posts support tags and `hero_image_id` integrated with the image selector/uploader.

### Changed

- **Image variants**: application config defines thumb/medium plus WebP variants (see `Application::bootstrap()`).
- **Documentation refresh**: README and supporting docs updated to reflect current app modules and architecture.

## [0.1.6-alpha] - 2025-11-08

### Added

- **Basketball Season Statistics System**: Complete CRUD management for basketball team season statistics
  - Player season statistics with team roster linkage (GP/GS/MIN and 18 stat fields: FGM, FGA, 3PM, 3PA, FTM, FTA, ORB, DRB, RB, AST, STL, BS, TRN, PF, TF, PTS)
  - Team season totals for unattributed plays (19 stat fields without GS)
  - Opponent season totals (19 stat fields without GS)
  - Three new controllers: `StatBasketSeasonPersonController`, `StatBasketSeasonTeamController`, `StatBasketSeasonOpponentController`
  - Four new admin templates (add/edit for player stats, edit for team/opponent stats)
  - Integration into TeamSeasons view template with conditional display for basketball (sport_id = 1)
  - Dynamic column filtering in stats display (hides columns with no data)
  - jQuery DataTable with sorting, searching, and responsive design
  - Proper entity associations: belongsTo TeamSeasonRosters (for players), belongsTo TeamSeasons (for team/opponent)

### Testing

- **19 New Unit Tests** for basketball season statistics controllers (343 total tests, 988 assertions)
  - StatBasketSeasonPersonControllerTest: 8 tests covering add GET/POST, edit GET/POST, delete, validation
  - StatBasketSeasonTeamControllerTest: 7 tests for edit GET/POST (create/update), delete
  - StatBasketSeasonOpponentControllerTest: 7 tests for edit GET/POST (create/update), delete
  - Three new test fixtures with realistic season totals data
  - Updated tests/schema.sql with three new stat tables

### Fixed

- Fixed association naming in Table models (TeamSeason → TeamSeasons for consistent naming)
  - StatBasketSeasonPersonTable: corrected belongsTo association to TeamSeasonRosters
  - StatBasketSeasonTeamTable: corrected belongsTo association to TeamSeasons
  - StatBasketSeasonOpponentTable: corrected belongsTo association to TeamSeasons
- Added comprehensive PHPDoc to all three season stat entities
- Added validation rules and buildRules to all three Table models
- Fixed controller type assertions and null-safe property access patterns
- All PHPStan errors resolved (0 errors)
- All PHPCS errors auto-fixed (13 violations in basketball_season_stats element)

## [0.1.5-alpha] - 2025-11-06

### Added

- **Basketball Game Statistics System**: Complete CRUD management for basketball game statistics
  - Player statistics with team roster linkage (GP/GS tracking, 18 stat fields)
  - Opponent player statistics with name-based tracking (no roster requirement)
  - Team-level statistics for plays not attributed to individuals (ORB/DRB/RB/TRN/TF/PTS)
  - Three new controllers: `StatBasketGamePersonController`, `StatBasketGameOpponentController`, `StatBasketGameTeamController`
  - Nine new admin templates for add/edit/view operations
  - Integration into Games view template with proper display of all stat types
  - Comprehensive validation: PTS required for players/opponents, GP defaults to 1, all team stats optional
  - Proper entity associations: belongsTo Games, Players link to TeamSeasonRosters

### Testing

- **26 New Unit Tests** for basketball statistics controllers (324 total tests, 914 assertions)
  - StatBasketGamePersonControllerTest: 13 tests, 97% coverage
  - StatBasketGameOpponentControllerTest: 8 tests, 97% coverage
  - StatBasketGameTeamControllerTest: 5 tests, 86% coverage
  - Three new test fixtures with proper database structures
  - Updated tests/schema.sql with all three stat tables

### Fixed

- Fixed association naming issues (TeamSeasons vs TeamSeason)
- Fixed property access for belongsTo associations (team_season_rosters → team_season_roster)
- Fixed team stats display (removed non-existent 'period' column, shows correct 6 fields)
- Fixed sorting with COALESCE for NULL-safe ordering
- All PHPStan errors resolved (type assertions, PHPDoc properties, proper initialization)
- All PHPCS violations auto-fixed (FQN references, DateTime namespace)

## [0.1.0-alpha] - 2025-08-10

### Added

- Initial CakePHP 5.x application skeleton and project structure.
- Authentication (login, register, logout, password reset) with CakePHP Authentication plugin.
- Admin prefixed area with access control (role & status checks) and dashboard.
- User management (listing, bulk activate/delete, registration toggle via `SiteOptions`).
- CSRF & FormProtection middleware/components; secure flash messaging.
- Error pages (`error400`, `error500`) with accessible, environment-aware templates.
- Bootstrap 5.3.2 integration, Bootstrap Icons, jQuery utilities.
- Reworked test suite (84 tests) with integration and model fixtures; unified identity helper.
- Migrations for initial schema and site options (autoId compatibility logic in CI).
- CI workflow (GitHub Actions) for multi-PHP matrix, coverage upload to Codecov, static analysis.

### Changed

- Inlined Authentication identifier config under Form authenticator to remove deprecation warnings.
- Simplified test identity injection to legacy session approach for stability.

### Fixed

- Resolved 500 errors in admin tests triggered by premature identity attribute injection.
- Eliminated deprecated `loadIdentifier()` warning by refactoring `Application::getAuthenticationService()`.
- Improved error template handling and layout selection to prevent rendering issues.

### Security

- Ensured CSRF and FormProtection remain enabled for admin and user routes during test runs.

---

Links:

- [Unreleased]: https://github.com/aught13/racerhistory/compare/v0.2.0-beta...HEAD
- [0.2.0-beta]: https://github.com/aught13/racerhistory/compare/v0.1.9-alpha...v0.2.0-beta
- [0.1.9-alpha]: https://github.com/aught13/racerhistory/compare/v0.1.6-alpha...v0.1.9-alpha
- [0.1.6-alpha]: https://github.com/aught13/racerhistory/compare/v0.1.5-alpha...v0.1.6-alpha
- [0.1.5-alpha]: https://github.com/aught13/racerhistory/compare/v0.1.0-alpha...v0.1.5-alpha
- [0.1.0-alpha]: https://github.com/aught13/racerhistory/releases/tag/v0.1.0-alpha
