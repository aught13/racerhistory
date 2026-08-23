# RBAC Hardening and Admin Access Refactor

Summary
-------
This document summarizes the RBAC hardening work landed on `feat/rbac-hardening`. It is grouped by policy layer, service layer, controller plumbing, and template/UI gating so reviewers can quickly scan intent, risk, and verification steps.

1) Policy layer
- Purpose: Centralize route-aware admin entry checks and normalize permission semantics (`none|own|all`).
- Key changes:
  - Added `src/Service/RbacPermissionService.php` to host the permission matrix, controller→model mapping, and helpers such as `canAccessAdminRequest()` and `hasAnyAdminAccess()`.
  - Updated request-level policy plumbing in `src/Policy/RequestPolicy.php` and application-level checks in `src/Policy/ApplicationPolicy.php` to use the new service instead of hard-coded role checks.
  - `UserPolicy` and `BlogPostPolicy` adjusted to align owner-management rules with permission-based gates rather than a hard `admin` role.

2) Service layer
- Purpose: Enforce identity-scoped query/delete operations and centralize permission-aware payload shaping.
- Key changes:
  - Admin services now accept an `identity` parameter when building DataTables payloads and when performing destructive actions. Files of note:
    - `src/Service/PersonAdminService.php`
    - `src/Service/OpponentAdminService.php`
    - `src/Service/PlaceAdminService.php`
    - `src/Service/SiteAdminService.php`
    - `src/Service/TeamAdminService.php`
    - `src/Service/TeamSeasonAdminService.php`
    - `src/Service/TeamSeasonRosterAdminService.php`
    - `src/Service/BlogPostsAdminService.php` (owner management gates)
  - Delete and bulk-delete paths sanitize the target ID set by intersecting with allowed IDs derived from the identity's `own`/`all` scopes.

3) Controller plumbing
- Purpose: Minimal controllers; delegate business logic to services and pass the authenticated identity down.
- Key changes:
  - Controllers were updated to pass the current authenticated identity into service calls (example: `Admin/PersonsController::delete()` → `PersonAdminService::delete($id, $identity)`).
  - `Admin/UsersController` fix: `accessibleFields` whitelist now includes `role_id` so the manage/add/edit forms persist role changes correctly.

4) Template / UI gating
- Purpose: Make action visibility reflect server-side permissions, avoid leaking action affordances, and provide clear UX for self-management.
- Key changes:
  - Global admin shell gating now uses `RbacPermissionService::hasAnyAdminAccess()` rather than a legacy `is_superuser` or hard-coded `admin` role. See `templates/layout/default.php` and `templates/element/Layout/admin_shell.php`.
  - Major admin templates were updated to hide edit/delete/bulk controls when the identity lacks the corresponding permission; representative files include:
    - `templates/Admin/Teams/*`, `templates/Admin/TeamSeasons/*`, `templates/Admin/Games/*`
    - `templates/Admin/Persons/*`, `templates/Admin/Places/*`, `templates/Admin/Sites/*`
    - UI elements: `templates/element/Admin/roster_management.php`, `templates/element/Admin/games_management.php` and the admin nav element.
  - `templates/Admin/Users/manage.php` now surfaces a self-password-change CTA and avoids exposing administrative password-change flows when not permitted.

Tests & Verification
- PHPCS: fixed docblock/style issues; `vendor/bin/phpcs` clean on patched files.
- PHPStan: no errors after changes.
- PHPUnit: full suite passed locally — 1269 tests, 4215 assertions, 3 skipped; non-failing warnings reported from `ImageStorageService` tests.
- Jest + JS Quality: 163 suites passed, 1628 tests; Prettier & ESLint pass.

Files touched (representative)
- `src/Service/RbacPermissionService.php`
- `src/Policy/RequestPolicy.php`, `src/Policy/ApplicationPolicy.php`, `src/Policy/UserPolicy.php`
- `src/Service/*AdminService.php` (Person, Opponent, Place, Site, Team, TeamSeason, TeamSeasonRoster, BlogPosts)
- `src/Controller/Admin/*Controller.php` (updated to pass identity)
- `templates/layout/default.php`, `templates/element/Layout/admin_shell.php`, and many `templates/Admin/*` files for action gating

Residual risks & followups
- Playwright E2E: no targeted E2E runs executed in this pass; recommend 1–3 targeted E2E specs (teams, team seasons, users manage) following Playwright local-run constraints.
- Audit of the RBAC mapping: the expanded `CONTROLLER_MODEL_MAP` and action maps should be spot-checked for any missed custom actions or plugin controllers.

How to review
1. Run style & static checks:

```bash
php vendor/bin/phpcs --standard=phpcs.xml src/ tests/ templates/
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G
```

2. Run unit tests (PHP/JS):

```bash
php vendor/bin/phpunit
npm run test:js
```

3. Inspect the docs/PR body and the `src/Service/RbacPermissionService.php` mapping for model/action correctness.

Notes
- The PR includes migrations and new seed data for initial RBAC roles/permissions; review `config/Migrations/*CreateRbacRolesAndPermissions.php` and `config/Seeds/RbacBootstrapSeed.php` before applying in production.

Contact
- If you want, I can also attach a short reviewer checklist and a small diff summary grouped by subsystem.
