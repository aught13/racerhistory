# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres (at the moment) to semantic versioning *starting with pre-release identifiers*.

## [Unreleased]

### Planned

- (placeholder) Additional domain models and admin features.
- (placeholder) Extended test coverage and performance improvements.

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

### Added (Unreleased) - Game Management System

- **Advanced Game Validation & Business Rules**: Comprehensive business logic for game entities
  - Future games allowed but cannot have scores/results set (`futureNoScore` validator)
  - Game dates must fall within season years for selected team season (`withinSeason` validator)
  - Cumulative scoring validation: period/overtime totals must equal game totals when `scoring_type='cumulative'`
  - Removed same-day game uniqueness constraint to allow multiple games per day
  - Enhanced `GamesTable` with `validateCumulativeTotals()` method and sport-aware validation
- **Sport Configuration System**: Dynamic sport settings for multi-sport support
  - New `SportConfigsTable` and `SportConfig` entity for flexible sport configuration
  - EAV (Entity-Attribute-Value) system for sport-specific game data storage
  - Period names (Half/Quarter/Inning), officials, and scoring types configurable per sport
  - Database migration `20251003140000_CreateSportConfigs.php` for sport_configs table
  - Enhanced games table migration `20251003141000_EnhanceGamesTableForMultiSport.php`
- **Smart Game Forms**: Dynamic, sport-aware admin interface
  - New `Admin\GamesController` with comprehensive CRUD operations and AJAX endpoints
  - Dynamic form fields that adapt based on selected team's sport configuration
  - Client-side JavaScript (`games_sport_dynamic.js`, `sport-aware-game-form.js`) for responsive forms
  - Server-side HTML fragment rendering for enhanced performance
  - Legacy period score mapping for backward compatibility
- **Enhanced Testing Coverage**: Comprehensive test coverage for new game features
  - Game validation tests: `GamesTableFutureValidationTest`, `GamesTableSeasonAndCumulativeTest`
  - Controller integration tests with authentication and form security
  - JavaScript test suite with 88%+ coverage including edge cases and error paths
  - Enhanced fixtures: `SportConfigsFixture`, `GamesFixture`, `GameEavFixture` for test stability
- **Template Modernization**: Bootstrap 5.3.2 compatible admin templates
  - New admin templates for Games, Opponents, Places, Sites, GameTypes management
  - Sport configuration views (`configs.php`, `edit_configs.php`) with dynamic field management
  - Responsive game management element for team season views
  - Accessibility improvements with ARIA labels and semantic markup

### Added (Unreleased)

- **Admin Sports Management**: Complete CRUD functionality for managing sports
  - New `Admin\SportsController` with index, view, add, edit, delete, and bulk operations
  - DataTables integration for responsive sports listing with search and pagination
  - Bootstrap 5.3.2 compatible templates with form validation
  - "Manage Sports" navigation link in admin interface
- **Admin Teams Management**: Complete CRUD functionality for managing teams
  - New `Admin\TeamsController` with index, view, add, edit, delete, and bulk operations
  - Team-to-Sport association management with dropdown selection
  - Gender classification support (Male/Female/Co-ed) with validation
  - Bootstrap 5.3.2 compatible templates with responsive design
  - "Manage Teams" navigation link in admin interface
- Enhanced `SportsTable` with validation rules (required, max length, unique sport names)
- Enhanced `TeamsTable` with comprehensive validation rules and Sports association
- Enhanced `Team` entity with proper type declarations and accessible fields
- Comprehensive test suite for Sports admin functionality (19 tests, 57 assertions)
- Comprehensive test suite for Teams admin functionality (15 tests, 35 assertions)
- Migration `20250811120000_CreateUsersTable` providing dedicated `users` table schema with adapter-aware datetime defaults.

- **Admin Seasons Management**: Full CRUD and bulk operations for seasons
  - New `Admin\\SeasonsController` with index, view, add, edit, delete, bulk and AJAX add endpoints
  - Templates: `templates/Admin/Seasons/{index,view,add,edit}.php` with Bootstrap 5.3.2-compatible markup and accessible elements
  - Tests: `tests/TestCase/Controller/Admin/SeasonsControllerTest.php` plus `Seasons` fixture

- **Admin TeamSeasons Management**: Full CRUD linking teams to seasons
  - New `Admin\\TeamSeasonsController` with index, view, add, edit, delete and bulk operations
  - `TeamSeasonsTable` improvements: validation, associations, and Timestamp behavior using `created_at`/`updated_at`
  - Templates: `templates/Admin/TeamSeasons/{index,view,add,edit}.php` and popup helpers for adding teams/seasons
  - Tests: `tests/TestCase/Controller/Admin/TeamSeasonsControllerTest.php` plus `TeamSeasons` fixture

- **Testing & Fixtures**: Added fixtures for `Seasons` and `TeamSeasons` (table `team_season`), and integration tests covering admin flows (index/view/add/edit/delete/bulk/ajax).

- **Frontend & Tooling**: Added `jest.setup.js` to provide a DOM shim for `HTMLFormElement.requestSubmit()` in JS tests, and a `security-report.json` placeholder produced by the audit tooling.

### Added (Unreleased) - Frontend & CI

- Extracted shared admin JavaScript into `webroot/js/admin.js` to centralize confirm-delete and toast helpers.
- Added Jest-based frontend tests under `webroot/js/tests/` covering confirm-delete modal behavior, fallback paths, invalid JSON handling, and toast helpers.
- Added `package.json` and npm dev tooling for JS tests, plus `.gitignore` entries for `node_modules` and JS coverage output.
- Introduced a combined GitHub Actions workflow `.github/workflows/combined-tests.yml` to run PHP unit tests (with coverage) and JS tests, then upload coverage artifacts to Codecov (PHP and JS flags).

### Added (Unreleased) - Team Season Rosters & Person Search

- **Team Season Rosters Management**:
  - New `Admin\\TeamSeasonRostersController` with add, edit, delete, bulk delete, AJAX add, and view actions
  - Dynamic roster management element (`templates/element/Admin/roster_management.php`) embedded in Team Seasons view for inline roster oversight
  - Roster add/edit forms support on‑the‑fly person creation via modal popup (re-usable popup form element)
  - Added `TeamSeasonRostersTable`, entity, fixture and comprehensive controller & table tests
  - Admin navigation updated with "Team Season Rosters" link
- **AJAX Person Search**:
  - Added `PersonsController::ajaxSearch()` endpoint providing debounced JSON search (display/first/last/full) limited to 30 results
  - Roster add/edit forms upgraded to hybrid input+select with client-side debounced search and preservation of current selection
  - Persons `ajaxAdd` response normalized message text ("The person has been saved.") and returns canonical `full_name`/display value
- **Team Season Images (View/Edit)**:
  - View page now loads season image dynamically (JS) mirroring edit page preview logic
  - Edit page pre-populates preview when existing image id present; includes variant (thumb) serving
- **Reusable Image Element**: `element/image_with_fallback.php` for robust image rendering with direct_url or fallback.

### Added (Unreleased) - Test Coverage & JS

- Expanded Jest test suites (`admin.more.test.js`, `person-image.extra.test.js`) increasing JS coverage to: Statements 82.32%, Branches 69.5%, Functions 96.15%, Lines 86.27%
- Added coverage of confirm-delete modal edge cases (single id, array ids, missing ids, temp form fallback, Bootstrap show event) and image selector upload preview path
- New PHPUnit tests for Team Season Rosters (controller + table) raising overall PHP test count to 205 tests / 500 assertions

### Added (Unreleased) - Images

- Public image storage: new public storage path `webroot/img/storage/{YYYY}/{MM}/UUID.ext` and accompanying controller/service support. JSON responses include both `url` (serve action) and `direct_url` (public file path).
- Image upload endpoints, `ImageProcessor` and `ImagesTable` improvements: variant generation metadata and stricter mime validation.
- Admin image templates and JS helpers for browsing/picking images (templates and `webroot/js/person-image.js` present). Basic Jest tests for frontend image picker utilities added under `webroot/js/tests/`.
- Fixtures and basic controller tests for images: `tests/Fixture/ImagesFixture.php` and `tests/TestCase/Controller/Admin/ImagesControllerTest.php` (initial coverage for upload/browse flows).

- **TeamSeasons Rich Content & Images**:
  - Added TinyMCE editors to Team Seasons add/edit (`team_season_preview`, `team_season_recap`) matching Persons bio configuration (self-hosted TinyMCE, image upload handler).
  - Added image upload/selection field with live preview on Team Seasons add/edit mirroring Persons image handling.
  - New element `templates/element/team_season_image.php` for consistent thumbnail rendering (used in index & view pages) with debug comments to aid test diagnostics.
  - Updated Team Seasons index to display thumbnail alongside team name; view now renders sanitized rich HTML for preview & recap (script/style stripped) and shows season image via serve endpoint.
  - Extended fixtures (`TeamSeasonsFixture`) with `team_season_image` sample and tests asserting presence of image element & rich text fields.

### Changed (Unreleased)

- **Code Quality Improvements**: All Sports and Teams-related files now pass PHPCS and PHPStan validation
- Enhanced `Sport` entity with proper property type annotations for better static analysis
- Enhanced `Team` entity with proper property type annotations for better static analysis
- Test bootstrap now aliases `default` to isolated `test` connection; removed manual seeding in favor of fixtures only.
- Refactored `UsersController::register()` formatting for standards compliance.
- Routes and installer tweaks: `config/routes.php` and `src/Console/Installer.php` updated to reflect new image-serving routes and setup steps.
- **ImagesController Diagnostics**: Strengthened storage directory creation & permission repair logic (writability checks, umask handling, group inheritance, verbose error logging, fallback low-level write) for more reliable image uploads across environments.
- **TeamSeasons View**: Reworked roster integration to use dedicated element with bulk actions & DataTables sorting.
- **Admin Navigation**: Added Team Season Rosters link; reorganized for new domains.

### Fixed (Unreleased) - Recent

- Null access warnings in `TeamSeasonRostersController::ajaxAdd` (person display construction) resolved via post-save person fetch & null-safe assembly
- `admin.js` single numeric id handling normalized (array coercion) preventing TypeError in tests
- Persons AJAX add response message aligned with Cake convention ("The person has been saved.") and tests updated accordingly
- Team Seasons view image display stabilized with client-side deferred load (prevents brittle server assertions)

### Fixed (Unreleased)

- **Form Security**: Resolved CSRF/Security token conflicts in admin forms by separating edit and delete actions
- Eliminated production DB leakage into tests by enforcing in-memory SQLite (unless explicitly forcing MySQL).
- Resolved PHPCS violations across controllers, tables, and test cases; suite now standards-clean.
- Stabilized Teams admin integration tests:
  - Added test guards to retain flash messages where views consume them.
  - Guarded DateTime formatting in `templates/Admin/Teams/view.php` to avoid 500s when timestamps are strings in test fixtures.
  - Removed temporary diagnostic logs used during debugging.
  - Image storage path: removed obsolete migrations that attempted to add a non-portable storage subdir; migrations cleaned to use a single `CreateImagesTable` migration and the autoId declaration adjusted for compatibility.
  *Note*: Legacy fixes retained above; new fixes listed in "Fixed (Unreleased) - Recent" subsection.

### Security (Unreleased)

- Removed redundant manual seed path reducing risk of unintended data mutation in alternate environments.

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

- [Unreleased]: https://github.com/aught13/racerhistory/compare/v0.1.0-alpha...HEAD
- [0.1.0-alpha]: https://github.com/aught13/racerhistory/releases/tag/v0.1.0-alpha
