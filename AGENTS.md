# Agent Instructions: CakePHP 5 Platform

## 🎯 Role & Intent
You are an expert CakePHP 5 (5.2+), Hotwire, and Vite engineer. Your goal is high-reliability, low-debt implementation using Service-Layer architecture, strict type safety, and optimized dual-stack testing.

## ✅ Runtime Baseline
- **PHP Version**: 8.2 to 8.5+ compatible (target syntax written to PHP 8.2 baseline).
- **Frontend Runtime**: Entry via Vite `js/main.js` (dist manifest-backed in production).
- **Legacy Paths**: Purely historical assets live under `js/legacy/`. Do not add new features or global `window.*` compatibility bridges there.

## 🏗️ Architecture & Component Rules
- **Test-Driven Assertions**: Every new function, branching path (`if/else`), and business rule mutation must have a dedicated test case. Ensure tests explicitly assert both successful outcomes and boundary/error conditions.
- **Service Layer First**: NEVER put business logic in Controllers. Controllers only extract request data, handle redirects, and call a Service (e.g., `src/Service/*`).
- **Dependency Injection**: Use constructor DI in Services. Fall back to `TableRegistry::getTableLocator()->get()` only if structurally necessary.
- **AuthN / AuthZ**: Middleware order is CSRF → Authentication → Authorization. Do not implement `AuthorizationIdentityInterface` on the `User` entity; authorization decorates the identity automatically.
- **Admin Access Control**: Admin controllers extend `src/Controller/Admin/AppController.php`. Access uses request-level authorization evaluated via `src/Policy/RequestPolicy.php`.
- **Frontend State**: All modern JavaScript must initialize on the `turbo:load` event. Utilize Bootstrap data-attributes before writing custom Stimulus controllers.

## 🛑 Definition of Done & Quality Checks
Before completing any task, you must verify the code against this checklist:
- [ ] Business logic resides completely inside an isolated Service class.
- [ ] Every new or modified function has an accompanying unit test block.
- [ ] Test cases explicitly cover all critical assertions, positive paths, and error states.
- [ ] PHPUnit test coverage targets are met with zero testing regressions.
- [ ] Jest ESM tests exist for any accompanying frontend mutations.
- [ ] Static analysis passes perfectly without baseline adjustments.

### Execution Commands (Run in This Exact Order)
1. **JS Unit Testing**: `npm run test:js` (Jest + ESM execution targeting `js/tests/`) [1]
2. **PHP Unit Testing**: `composer test` (Executes PHPUnit 10+ configured via `phpunit.ci.xml`) [1]
3. **PHP Code Style**: `composer cs-check` (Strictly maps to `php vendor/bin/phpcs --standard=phpcs.xml`) [1]
4. **PHP Static Analysis**: `composer phpstan:check` (Level 7+, 1GB memory limits enforced) [1]
5. **E2E Integration**: `npx playwright test` (Subject to the local optimization rules below) [1]

## 🧪 Testing Guidelines & Gotchas
- **Integration POSTs**: You must explicitly enable security tokens by calling `$this->enableCsrfToken(); $this->enableSecurityToken();`.
- **Flash Messages**: Call `$this->enableRetainFlashMessages();` before making assertions on flash values after a render.
- **Authentication**: Use the session injection pattern from `tests/TestCase/Support/AuthTestTrait.php`. Avoid modifying request attributes directly.
- **Coverage Enforcement**: Codecov thresholds require absolute coverage of **≥ 98% for PHP** and **≥ 88% for JS**.

---

# Playwright E2E Test Execution Rules (Large Suite Optimization)

Operating Environment: Local VS Code on Arch Linux. Because the test suite is exceptionally large, you must strictly follow these constraints to prevent system lag, OOM (Out of Memory) crashes, and window focus hijacking.

## 1. Test Execution Boundaries (Local Run Restrictions)
- **NEVER** run global test commands like `npx playwright test` without specific target scopes.
- **ALWAYS** isolate your target. Run exactly one file, line number, or tag name block at a time:
  - Run a specific file: `npx playwright test path/to/file.spec.ts` [1]
  - Run a specific test line: `npx playwright test path/to/file.spec.ts:14` [1]
  - Run by name tag: `npx playwright test -g "should login successfully"` [1]
- **ALWAYS** restrict execution to a single browser project to save CPU cycles: `--project=chromium`.
- **ALWAYS** run single specs via the local helper `./scripts/e2e-local.sh -- --project=chromium e2e/turbo-integration.spec.js --trace on`

## 2. Resource & Process Management (Arch Linux Specifics)
- **ALWAYS** cap parallel workers to half of your available logical CPU cores or fewer to ensure your user interface stays responsive: `--workers=50%`.
- If an end-to-end integration test hangs, do not wait indefinitely. Force a strict timeout for your debug run: `--timeout=20000` (20 seconds).

## 3. Debugging and Visual Modes
- Do **NOT** use the `--headed` flag by default. Arch systems running Wayland/X11 will spam modal windows and steal desktop focus.
- To debug UI selectors safely, utilize the Playwright UI mode targeted explicitly to a single file: `npx playwright test path/to/file.spec.ts --ui`.
- Alternatively, direct the engineer to view generated trace files via `npx playwright show-trace path/to/trace.zip` instead of re-running failing tests repeatedly.

## 4. State & Authentication Efficiency
- Always check if `playwright.config.ts` utilizes a `storageState` configuration (e.g., `auth.json`).
- If debugging a test that requires administrative or user authentication, verify if a valid `auth.json` exists in the project root or build directory before executing. Do not force a full browser login loop if an authenticated state can be reused.

## 5. Workflow for Fixing Broken Tests
1. **Locate:** Find the exact failing test path from the developer's prompt or local logs.
2. **Isolate:** Run that single test line with `--workers=1` and `--grep` parameters.
3. **Analyze:** Inspect the `test-results/` directory for the specific failure snippet, screenshot, or trace file.
4. **Fix & Verify:** Apply the fix to the source code, then run **ONLY** that specific test block to verify compliance. Once it passes, stop. Do not attempt a regression run of the entire suite.

