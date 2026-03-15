# GitHub CI Workflow Updates

## Summary of Changes

The `.github/workflows/ci.yml` file has been updated to support:
1. **ESM-enabled Jest tests** with proper Node.js flags
2. **Playwright E2E tests** for Turbo Frame components
3. **ESLint and Prettier** checks on every commit/PR
4. **JavaScript coverage** reporting to Codecov
5. **Comprehensive test artifacts** for debugging

---

## Updated Jobs

### 1. js-tests (JavaScript Unit Tests)

**New Steps Added:**
- ✅ **Run ESLint** - Code quality checks with zero-warning policy
- ✅ **Check Prettier formatting** - Ensures consistent code style
- ✅ **Upload JS coverage to Codecov** - Tracks JS coverage separately with `jstests` flag
- ✅ **ESM Support** - Added `NODE_OPTIONS: --experimental-vm-modules` for native ESM

**Before:**
```yaml
- name: Run JS tests
  run: npm run test:js -- --ci
```

**After:**
```yaml
- name: Run ESLint
  run: npm run lint:js

- name: Check Prettier formatting
  run: npm run format:js:check

- name: Run Jest tests with coverage (ESM)
  run: npm run test:js -- --ci --coverage
  env:
    NODE_OPTIONS: --experimental-vm-modules

- name: Upload JS coverage to Codecov
  uses: codecov/codecov-action@...
  with:
    files: coverage-js/clover.xml
    flags: jstests
```

### 2. e2e-tests (NEW JOB - Playwright E2E Tests)

**Entirely new job for end-to-end testing:**
- Runs after PHP tests complete successfully (`needs: testsuite`)
- Sets up full application stack:
  - MySQL database service
  - PHP 8.3 with Composer dependencies
  - Node.js 20 with npm packages
  - Playwright browsers (Chromium)
- Runs database migrations
- Starts CakePHP dev server on port 8765
- Executes Playwright tests against live application
- Captures and uploads test artifacts

**Key Features:**
```yaml
- name: Install Playwright Browsers
  run: npx playwright install --with-deps chromium

- name: Start CakePHP development server
  run: |
    php bin/cake server -p 8765 -H localhost &
    echo $! > server.pid
    # Wait for server to be ready...

- name: Run Playwright E2E tests
  run: npm run test:e2e
  env:
    PLAYWRIGHT_BASE_URL: http://localhost:8765
    CI: true

- name: Upload Playwright report
  uses: actions/upload-artifact@...
  if: always()
  with:
    name: playwright-report
    path: playwright-report/
```

---

## Artifacts Uploaded

| Artifact Name | Content | When | Retention |
|---------------|---------|------|-----------|
| `js-coverage` | Jest coverage reports | Always | Default |
| `playwright-results` | JUnit XML test results | Always | 30 days |
| `playwright-report` | HTML test report + screenshots | Always | 30 days |
| `playwright-traces` | Debug traces + videos | On failure | 30 days |

---

## Codecov Integration

### PHP Coverage
- **Flag**: `php81-lowest,unittests`
- **File**: `coverage.xml`
- **Job**: `testsuite` (PHP 8.1 with lowest dependencies)

### JavaScript Coverage
- **Flag**: `jstests`
- **File**: `coverage-js/clover.xml`
- **Job**: `js-tests`
- **Fail CI on error**: `false` (non-blocking)

---

## CI Workflow Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    GitHub CI Workflow                    │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
   ┌─────────┐      ┌─────────────┐    ┌────────────┐
   │testsuite│      │coding-standard│   │js-tests   │
   │PHP Tests│      │PHPCS & PHPStan│   │Jest + ESM  │
   └────┬────┘      └──────────────┘    └────────────┘
        │
        │ needs: testsuite
        ▼
   ┌─────────────┐
   │e2e-tests    │
   │Playwright   │
   │Live App Test│
   └─────────────┘
```

---

## Environment Variables

### js-tests Job
```yaml
NODE_OPTIONS: --experimental-vm-modules  # Enable ESM in Jest
```

### e2e-tests Job
```yaml
PLAYWRIGHT_BASE_URL: http://localhost:8765  # App URL for tests
CI: true                                     # CI environment flag
DATABASE_TEST_URL: mysql://test_user:test_password@127.0.0.1:3306/racerhistory_test
```

---

## Running Tests Locally (Same as CI)

### Jest Tests (with ESM)
```bash
NODE_OPTIONS=--experimental-vm-modules npm run test:js -- --ci --coverage
npm run lint:js
npm run format:js:check
```

### Playwright E2E Tests
```bash
# Terminal 1: Start server
php bin/cake server -p 8765

# Terminal 2: Run tests
PLAYWRIGHT_BASE_URL=http://localhost:8765 npm run test:e2e
```

---

## Debugging CI Failures

### Jest Tests Failing
1. Check ESLint output for syntax issues
2. Verify Prettier formatting: `npm run format:js`
3. Run tests locally with same Node version (20)
4. Check coverage report artifact

### Playwright Tests Failing
1. Download `playwright-report` artifact from GitHub Actions
2. Open `playwright-report/index.html` in browser
3. Review screenshots and videos
4. Download `playwright-traces` (if available)
5. Open traces in Playwright trace viewer: `npx playwright show-trace trace.zip`

### Coverage Not Uploading to Codecov
1. Verify `CODECOV_TOKEN` secret is set
2. Check that coverage files exist:
   - PHP: `coverage.xml`
   - JS: `coverage-js/clover.xml`
3. Review Codecov action logs

---

## What Gets Tested

### Jest (Unit/Integration Tests)
- ✅ Admin.js utility functions
- ✅ Form handling and validation
- ✅ Image selector and cropper
- ✅ Sports-aware game forms
- ✅ CSS and style regressions
- ✅ DOM manipulation utilities

### Playwright (E2E Tests)
- ✅ Turbo Frame lazy loading (game stats)
- ✅ Turbo Frame navigation (seasons table)
- ✅ Turbo Frame tabs (people game logs)
- ✅ Turbo Drive page navigation
- ✅ Cache behavior and form submissions
- ✅ Event lifecycle and scroll restoration

---

## CI Performance

Estimated run times:
- **testsuite**: ~3-5 minutes (PHP tests with DB)
- **coding-standard**: ~1-2 minutes (PHPCS + PHPStan)
- **js-tests**: ~30-60 seconds (Jest + linting)
- **e2e-tests**: ~2-4 minutes (Playwright + app setup)

**Total CI time**: ~8-12 minutes for full pipeline

---

## Next Steps

1. ✅ **CI is ready** - Push to master/main or create PR to trigger
2. ⚠️ **Watch first run** - Some E2E tests may need route/auth adjustments
3. ✅ **Review artifacts** - Check that reports are being generated correctly
4. ✅ **Monitor Codecov** - Verify coverage data appears correctly

## Maintenance

### Adding New Tests
- **Jest**: Place in `webroot/js/tests/`, use ESM syntax
- **Playwright**: Place in `e2e/`, use `.spec.js` extension

### Updating Browser Versions
```yaml
- name: Install Playwright Browsers
  run: npx playwright install --with-deps chromium firefox webkit
```

### Adjusting Coverage Thresholds
Edit `codecov.yml`:
```yaml
coverage:
  status:
    project:
      js:
        target: 88  # Adjust as needed
```

---

**Last Updated**: 2026-03-01
**Status**: ✅ Ready for production use
