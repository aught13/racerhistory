# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres (at the moment) to semantic versioning *starting with pre-release identifiers*.

## [Unreleased]

### Planned

- (placeholder) Additional domain models and admin features.
- (placeholder) Extended test coverage and performance improvements.

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

### Added (Unreleased) - Images

- Public image storage: new public storage path `webroot/img/storage/{YYYY}/{MM}/UUID.ext` and accompanying controller/service support. JSON responses include both `url` (serve action) and `direct_url` (public file path).
- Image upload endpoints, `ImageProcessor` and `ImagesTable` improvements: variant generation metadata and stricter mime validation.
- Admin image templates and JS helpers for browsing/picking images (templates and `webroot/js/person-image.js` present). Basic Jest tests for frontend image picker utilities added under `webroot/js/tests/`.
- Fixtures and basic controller tests for images: `tests/Fixture/ImagesFixture.php` and `tests/TestCase/Controller/Admin/ImagesControllerTest.php` (initial coverage for upload/browse flows).

### Changed (Unreleased)

- **Code Quality Improvements**: All Sports and Teams-related files now pass PHPCS and PHPStan validation
- Enhanced `Sport` entity with proper property type annotations for better static analysis
- Enhanced `Team` entity with proper property type annotations for better static analysis
- Test bootstrap now aliases `default` to isolated `test` connection; removed manual seeding in favor of fixtures only.
- Refactored `UsersController::register()` formatting for standards compliance.
- Routes and installer tweaks: `config/routes.php` and `src/Console/Installer.php` updated to reflect new image-serving routes and setup steps.

### Fixed (Unreleased)

- **Form Security**: Resolved CSRF/Security token conflicts in admin forms by separating edit and delete actions
- Eliminated production DB leakage into tests by enforcing in-memory SQLite (unless explicitly forcing MySQL).
- Resolved PHPCS violations across controllers, tables, and test cases; suite now standards-clean.
- Stabilized Teams admin integration tests:
  - Added test guards to retain flash messages where views consume them.
  - Guarded DateTime formatting in `templates/Admin/Teams/view.php` to avoid 500s when timestamps are strings in test fixtures.
  - Removed temporary diagnostic logs used during debugging.
  - Image storage path: removed obsolete migrations that attempted to add a non-portable storage subdir; migrations cleaned to use a single `CreateImagesTable` migration and the autoId declaration adjusted for compatibility.

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
