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

This project uses **PHPUnit 12.x**.

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

1. **PHPUnit 12.x Compatibility**:

    - Avoid complex PHPUnit configurations in CI
    - Local runs: just `vendor/bin/phpunit` (no coverage by default)
    - CI coverage matrix: `vendor/bin/phpunit --configuration phpunit.ci.xml`
    - Core tests (Application, View, Model, Component) must pass
    - Integration tests may have expected failures in CI due to environment differences

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
