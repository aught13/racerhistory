# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres (at the moment) to semantic versioning *starting with pre-release identifiers*.

## [Unreleased]

### Planned

- (placeholder) Additional domain models and admin features.
- (placeholder) Extended test coverage and performance improvements.

### Added (Unreleased)

- Migration `20250811120000_CreateUsersTable` providing dedicated `users` table schema with adapter-aware datetime defaults.

### Changed (Unreleased)

- Test bootstrap now aliases `default` to isolated `test` connection; removed manual seeding in favor of fixtures only.
- Refactored `UsersController::register()` formatting for standards compliance.

### Fixed (Unreleased)

- Eliminated production DB leakage into tests by enforcing in-memory SQLite (unless explicitly forcing MySQL).
- Resolved PHPCS violations across controllers, tables, and test cases; suite now standards-clean.

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
