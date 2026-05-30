<!-- Repo-specific Copilot instructions (CakePHP 5.2+) -->

## Project snapshot

- CakePHP app with admin UI + public blog + public image serving; overview: `APPLICATION_SUMMARY.md`.
- Routing: `config/routes.php` (Admin prefix `/admin`, public blog `/blog`, public image `/images/serve/:id`, read-only JSON API `/api/v1/*`).

## Architecture & conventions (important)

- **Service layer owns business logic**: use `src/Service/*` (e.g. `GameUpsertService`, `ImageStorageService`, `BasketballStatsService`). Controllers orchestrate requests and delegate to services.
- Services typically use optional constructor DI and fall back to `TableRegistry::getTableLocator()->get()` (see `src/Service/GameUpsertService.php`).
- Admin controllers extend `src/Controller/Admin/AppController.php` (sets admin layout and enforces authz).

## AuthN/AuthZ (do this, don’t fight it)

- Middleware order is CSRF → Authentication → Authorization (see `src/Application.php`).
- Admin access is **request-level authorization**: `Admin/AppController::beforeFilter()` calls `$this->Authorization->authorize($this->request, 'accessAdmin')` (policy: `src/Policy/RequestPolicy.php`).
- Don’t implement `AuthorizationIdentityInterface` on the User entity; Authorization decorates the identity automatically (details in `AUTHORIZATION_SETUP.md`).
- CakeDC/Users provides public auth UI endpoints (`/login`, `/logout`, `/register`); app owns admin gating.

## Domain-specific hotspots

- **Sport-aware Games**: EAV metadata + validation live in services (`GameService`, `SportConfigService`, `GameEavUiService`). Admin forms are dynamic via `webroot/js/sport-aware-game-form.js` + `webroot/js/games_sport_dynamic.js`.
- **Images**: upload/variants/tagging pipeline is centralized (`ImageStorageService` → `ImageProcessor` → `TaggingService`); variants configured in `src/Application.php` (`Images.variants`). More detail: `README_IMAGE_STORAGE.md`.

## Dev workflow (preferred commands)

- PHP tests: `php vendor/bin/phpunit; echo EXIT:$?` (CI spans PHPUnit 10–12; keep tests compatible with PHPUnit 10).
- PHP quality: `php vendor/bin/phpcs --standard=phpcs.xml src/ tests/; echo EXIT:$?` and `php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`.
- JS quality: ESLint/Prettier/Jest apply to `webroot/js/` (excluding `webroot/js/tinymce/`); tests live in `webroot/js/tests/`; coverage thresholds enforced via `codecov.yml`.

## Coverage & compatibility

- Add/extend **PHPUnit** tests and **Jest** tests when changing behavior; keep overall coverage **≥ 80%** (PHP + JS) as the initial bar for new changes.
- PHP coverage run: `php vendor/bin/phpunit --configuration phpunit.ci.xml` (produces `coverage.xml`).
- JS coverage run: `npx jest --coverage` (or `npm run test:js`).
- Note: CI/Codecov enforces stricter targets from `codecov.yml` (currently: PHP **98%**, JS **88%**, branches **80%**). Prefer meeting those when practical.
- Keep tests compatible with **PHP 8.2–8.5+** and the CI’s PHPUnit range (write to PHPUnit 10-era APIs; avoid relying on newer-only features).

## Test gotchas (this repo)

- Integration POSTs: enable tokens (`$this->enableCsrfToken(); $this->enableSecurityToken();`).
- Flash assertions after render: call `$this->enableRetainFlashMessages();` first.
- Auth in integration tests: use `tests/TestCase/Support/AuthTestTrait.php` session injection pattern (avoid request-attribute hacks).

## Additional guidance for Copilot / AI contributors

- Always create unit and integration tests for any controller that renders a page load. Include at minimum:
	- **PHPUnit** tests covering server-side controller behavior and template output.
	- **Jest + ESM** tests covering the page's frontend behavior and DOM interactions.
	- **CSS** tests (node-level) that assert required selectors and theme adjustments are present.
    - **ECE tests** that cover the full stack of a page load, including frontend and backend behavior, and assert the expected output in the rendered page.
- All frontend page loads must fully utilize the project's Hotwire/Turbo frontend configuration and lifecycle (initialize on `turbo:load` as well as `DOMContentLoaded`).
- Jest tests should use ES module imports for the code under test (prefer `import` and `export`), and tests/helpers should be converted to ESM when adding new modules.
- Test and quality commands must be run in a blocking terminal and their exit codes observed. When automating or invoking from scripts, ensure the following tools are always executed and awaited in this order where practical:
	1. `jest` (or `npm run test:js`)
 2. `phpunit`
 3. `eslint` followed by `prettier --check` (or `prettier --write` when fixing)
 4. `phpcs` then `phpcbf` (allow phpcbf to fix and re-run phpcs)
 5. `phpstan`

	Use the repository task runners or the `run_in_terminal` pattern so the process waits for each command to finish and checks the exit code before proceeding.

## Migrations (avoid CI breakage)

- Implement reversible `up()` and `down()`.
- TimestampBehavior tables must use `created`/`modified` as `datetime`.
- Match the exact `$autoId` property type expected by `Migrations\BaseMigration` in this install.
