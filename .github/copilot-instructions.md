<!-- Use this file to provide workspace-specific custom instructions to Copilot. For more details, visit https://code.visualstudio.com/docs/copilot/copilot-customization -->

## Feedback Guidelines

**Be direct and critical.** Use deep thinking and best logic when evaluating requests. Do not use excessive praise or positive affirmations. Be constructively critical when inefficient approaches are being taken. Focus on technical merit, performance, maintainability, and following established patterns rather than encouragement.

This is a CakePHP 5.x web application project. Please generate code and suggestions that follow CakePHP 5.x best practices.

Whenever you create or update a controller, component, or model, also create or update corresponding unit tests following CakePHP 5.x conventions. Unit tests should cover all public methods and expected behaviors, including validation and error handling.

When generating templates, ensure they are compatible with Bootstrap 5.3.2 and follow the responsive design principles. Include appropriate ARIA attributes for accessibility.

## Migration Guidelines

When creating or modifying CakePHP migrations:

1. **Property Type Declarations**: Always check the parent AbstractMigration class for property type requirements:
    - Use `public bool $autoId = false;` for CakePHP migrations 4.x+ compatibility
    - Use `public $autoId = false;` for older versions
    - Check `vendor/cakephp/migrations/src/AbstractMigration.php` for current declaration
    - **CI Compatibility:** The CI pipeline auto-adapts migration property typing at runtime. See `.github/workflows/ci.yml` (step: "Fix migration compatibility"). It inspects the parent class and rewrites all migration files to match the required property type (typed or untyped) before running migrations. This ensures migrations work across local and CI environments even if the required property type changes between CakePHP versions.
    - **Contributor Note:** Always use the property type required by your local CakePHP version. CI will rewrite as needed. Do not attempt runtime adaptation in migration code; rely on CI logic.

2. **Database Compatibility**: Ensure migrations work across different MySQL versions:
    - Use standard SQL data types
    - Avoid version-specific features unless necessary
    - Test with MySQL 8.0 (current CI environment)

3. **Rollback Support**: Always implement both `up()` and `down()` methods for reversible migrations

## PHPUnit Testing Guidelines

### Test Execution Strategy

This project uses multiple PHPUnit majors across the CI matrix:

- PHP 8.1 (lowest deps, coverage job) installs **PHPUnit 10.x**.
- PHP 8.2 / 8.3 (highest deps) install **PHPUnit 11.x/12.x** (whichever the resolver selects within the composer constraint).

Author tests so they are compatible with PHPUnit 10 features (avoid 11/12-only XML config elements or attributes). Do not rely on deprecated 9.x syntax.

Primary local command (auto-discovers tests via `phpunit.xml.dist`):

```bash
vendor/bin/phpunit
```

Coverage (CI only – requires Xdebug or PCOV) uses a separate config with coverage enabled:

```bash
vendor/bin/phpunit --configuration phpunit.ci.xml
```

If you need an ad‑hoc subset without configuration (bypassing discovery logic), you can still run:

```bash
vendor/bin/phpunit --no-configuration --bootstrap tests/bootstrap.php tests/TestCase/Some/SpecificTest.php
```

### Test Writing Standards

1. **Controller Tests**:
    - Use CakePHP's `IntegrationTestTrait` for HTTP testing
    - Handle authentication requirements in CI environments
    - Expect redirects for unauthenticated admin access
    - Include CSRF token handling for POST requests

2. **Model Tests**:
    - Test validation rules thoroughly
    - Test custom finder methods
    - Use fixtures for consistent test data
    - Test entity accessor/mutator methods

3. **Component Tests**:
    - Mock external dependencies
    - Test all public methods
    - Test error conditions and edge cases

4. **Template Requirements**:
    - Ensure all referenced templates exist (e.g., `templates/Pages/display/home.php`)
    - Missing templates cause 500 errors in controller tests

### CI Compatibility Notes

1. **PHPUnit Multi-Version Compatibility (10–12)**:
    - Keep XML configs limited to syntax valid in PHPUnit 10 (phpunit.ci.xml uses a 10.x schema; phpunit.xml may target 12.x but must not introduce incompatible constructs needed by shared tests).
    - Local quick run: `vendor/bin/phpunit` (uses phpunit.xml).
    - Coverage run (CI only): `vendor/bin/phpunit --configuration phpunit.ci.xml` (generates coverage.xml using `<coverage><report>` block).
    - Do not add `<coverage>` inside `<logging>` in phpunit.ci.xml (10.x warning trigger).
    - Core tests (Application, View, Model, Component) must always pass in every matrix job.
    - Integration tests should also pass; do not intentionally rely on environment-specific skips.

2. **Database Setup**:
    - Tests use MySQL 8.0 in CI with test database `racerhistory_test`
    - Ensure `DATABASE_TEST_URL` environment variable is properly configured
    - Bootstrap must successfully establish database connections

### Pre-commit PHPCS

Before committing code that changes PHP files, run the same PHPCS check the CI uses so issues are caught locally early. The CI pipeline runs PHPCS and pipes the checkstyle output to `cs2pr`; replicate that locally with:

```bash
vendor/bin/phpcs --report=checkstyle | cs2pr
```

If you want to auto-fix trivial issues first, run:

```bash
vendor/bin/phpcbf --standard=phpcs.xml
```

Run PHPCS and fix any remaining issues (or follow the `cs2pr` output) before committing. This keeps local commits aligned with CI expectations.

3. **Deprecation Handling**:
    - CakePHP 5.x may show deprecation warnings - these are acceptable
    - Use `patch()` instead of `set()` for entity updates to avoid deprecations
    - Authentication plugin deprecations are expected and handled

### Test Organization

- **Core Tests**: Application, View, Model, Component (must pass 100%)
- **Integration Tests**: Controller tests (may have CI-specific failures)
- **Test Discovery**: Should find all 78+ tests across the test suite

When adding new tests, ensure they follow these patterns and are compatible with the minimal execution approach used in CI.

## Unit Test Constraints & Guardrails

To keep the test suite stable across local and CI runs, follow these non-negotiable constraints:

1. Migrations & TimestampBehavior
    - Any table that uses Cake's TimestampBehavior must define `created` and `modified` columns as `datetime` (not `timestamp` or `text`). This avoids type mismatches (DateTimeType) that break saves and cause controller tests to fail.
    - Do not use adapter-specific TEXT fallbacks for these columns; rely on TimestampBehavior to populate values at save time.
    - Always include reversible `up()` and `down()` methods when changing schema.

2. Migrations `autoId` Property
    - The `$autoId` property declaration in migrations must exactly match the parent `Migrations\AbstractMigration` signature used by the installed version.
    - If the parent is typed, use: `public bool $autoId = false;`
    - If the parent is untyped, use: `public $autoId = false;`
    - When in doubt, check `vendor/cakephp/migrations/src/AbstractMigration.php` and update all new migrations consistently to avoid CI fatals.

## Testing gotchas we've seen in this repo

Two recurring small test flakiness sources have shown up in the test suite: 1) flash messages consumed by the view and 2) timestamp fields being strings in some test contexts. Below are concrete mitigations to use when writing controllers, views and tests:

- Flash messages are stored in session and the view/FlashHelper will "consume" them during render. When a test needs to assert on a flash after rendering (not after a redirect), enable flash retention in the test:
    - In integration tests: call `$this->enableRetainFlashMessages();` before making the request. This prevents the test harness from discarding consumed messages and allows `assertFlashMessage()` to find them.

- Prefer explicit, test-friendly timestamp handling:
    - Fixtures and migrations should provide DateTime-compatible values where possible (use DateTime objects in factories or ensure the migration default types are `datetime` and not strings). This keeps templates that call `$entity->created_at->format()` safe.
    - In templates, defensively handle timestamp fields in case they are strings in some test scenarios. For example:

        ```php
        if ($entity->created_at instanceof \DateTimeInterface) {
                echo h($entity->created_at->format('M j, Y g:i A'));
        } else {
                echo h($entity->created_at);
        }
        ```

These two patterns (use `enableRetainFlashMessages()` in tests and guard DateTime usage in templates) are low-cost and have stabilized our CI runs.

3. Controller Action Outcomes in Tests
    - Admin POST actions (approve/delete/bulk/toggle) must return a redirect response with a `Location` header (typically back to the index). Tests assert redirects via `assertRedirect()` or `assertRedirect('/admin/users')` and expect a flash message.
    - For POST requests in tests, always include CSRF and Security tokens: use `$this->enableCsrfToken(); $this->enableSecurityToken();`.

4. Authentication in Integration Tests
    - Inject authenticated users using the legacy `Auth` session array only (as done in `AuthTestTrait`). Avoid injecting request attributes to prevent intermittent FormProtection issues in CI.

5. Fixtures, Not Seeding
    - Do not seed data in test bootstrap. Use fixtures for deterministic baselines.
    - SiteOptions fixture must include `option_key = 'registration'` with `value = 'true'` as the default baseline; tests may toggle this value.

6. DB Environment Assumptions
    - CI runs tests on MySQL 8.0. Local test bootstrap defaults to in-memory SQLite unless explicitly forced to MySQL via environment. Keep schema and code adapter-agnostic and avoid DB-specific defaults where possible.

Add @codecov-ai-reviewer review-- the assistant will review the PR and make suggestions.

## JavaScript Workflow & Tooling

This project enforces modern JavaScript standards for all custom JS code (excluding third-party libraries like TinyMCE):

- **Linting:** All JS files in `webroot/js/` (except `webroot/js/tinymce/`) must pass ESLint checks using the config in `eslint.config.js`.
- **Testing:** Jest is used for unit and coverage testing of JS. All new JS features should include corresponding tests in `webroot/js/tests/`.
- **Coverage Targets:** Minimum coverage enforced: 88% for JS (lines), 80% for JS branch coverage. See `codecov.yml` and VS Code settings.
- **Pre-commit:** All JS code must pass lint, format, and test checks before commit (see `.git/hooks/pre-commit`).

## VS Code Configuration

- **Recommended Extensions:** See `.vscode/extensions.json` for required PHP/JS tooling (ESLint, Prettier, Jest, PHPCS, PHPStan, PHPUnit, Intelephense).
- **Settings:** `.vscode/settings.json` enforces linter/fixer ignores, coverage thresholds, and PHP/JS integration.
- **Tasks:** `.vscode/tasks.json` provides build/test/lint/format tasks for both PHP and JS. Use these for consistent local workflow.

## Commit & Branching Guidelines

- Use clear, descriptive commit messages (e.g., `fix: correct image upload preview`, `feat: add admin bulk delete`).
- Prefer feature branches for new work; open pull requests for review and CI validation.

## PHPCS Verbose Standard Usage (Always for Assistant Runs)

When the assistant (automation) runs PHPCS for diagnostic purposes, ALWAYS use the verbose, progress, and sniff-code enabled invocation limited to the first 200 lines to keep chat output compact:

```bash
php vendor/bin/phpcs --standard=phpcs.xml -p -s -v src/ tests/ | head -200
```

Rationale:
- `-p` shows progress across many files so long-running scans are visibly active.
- `-s` prints sniff codes to quickly map violations to their fixers/rules.
- `-v` adds context (file list, timings) useful for performance/regression spotting.
- `head -200` prevents flooding the conversation while still surfacing the first batch of issues (most actionable). If more context is needed, explicitly re-run without the `head` truncation.

Auto-fixing reminder:
```bash
php vendor/bin/phpcbf --standard=phpcs.xml
```
Then re-run the verbose PHPCS command above to confirm a clean state.

If a later task explicitly requests full, untruncated PHPCS output, omit the `| head -200` portion.
