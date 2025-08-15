<!-- Use this file to provide workspace-specific custom instructions to Copilot. For more details, visit https://code.visualstudio.com/docs/copilot/copilot-customization#_use-a-githubcopilotinstructionsmd-file -->

This is a CakePHP 5.x web application project. Please generate code and suggestions that follow CakePHP 5.x best practices.

Whenever you create or update a controller, component, or model, also create or update corresponding unit tests following CakePHP 5.x conventions. Unit tests should cover all public methods and expected behaviors, including validation and error handling.

When generating templates, ensure they are compatible with Bootstrap 5.3.2 and follow the responsive design principles. Include appropriate ARIA attributes for accessibility.

## Migration Guidelines

When creating or modifying CakePHP migrations:

1. **Property Type Declarations**: Always check the parent AbstractMigration class for property type requirements:

    - Use `public bool $autoId = false;` for CakePHP migrations 4.x+ compatibility
    - Use `public $autoId = false;` for older versions
    - Check `vendor/cakephp/migrations/src/AbstractMigration.php` for current declaration

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

3. **Deprecation Handling**:
    - CakePHP 5.x may show deprecation warnings - these are acceptable
    - Use `patch()` instead of `set()` for entity updates to avoid deprecations
    - Authentication plugin deprecations are expected and handled

### Test Organization

-   **Core Tests**: Application, View, Model, Component (must pass 100%)
-   **Integration Tests**: Controller tests (may have CI-specific failures)
-   **Test Discovery**: Should find all 78+ tests across the test suite

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
