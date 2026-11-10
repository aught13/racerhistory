# Testing Documentation (v0.2.0-beta)

## Overview

Comprehensive test coverage for the RacerHistory application spanning PHP backend and JavaScript frontend.

**Current Statistics:**
- **PHPUnit**: 1235 tests, 4056 assertions
- **Jest**: 1578 tests across 159 suites
- **JS Coverage**: 88.37% statements, 80% branches, 87.37% functions
- **Quality**: PHPStan 0 errors, PHPCS clean, ESLint clean, Prettier clean

## PHP Controller Tests

### Test Files Created

1. **SeasonsControllerTest.php** - Tests for `/seasons` and `/seasons/{id}` endpoints
2. **PeopleControllerTest.php** - Tests for `/people` and `/people/{id}` endpoints
3. **GamesControllerTest.php** - Tests for `/games` and `/games/{id}` endpoints
4. **StatsControllerTest.php** - Tests for `/stats` and `/stats/season/{id}` endpoints

### Test Structure

Each controller test includes:

- `testIndex()` - Verifies index page loads successfully
- `testIndexDisplaysData()` - Confirms data is loaded and set as view variables
- `testView()` - Tests individual resource view pages
- `testViewWithInvalidId()` - Ensures 404 errors for non-existent resources
- `testViewSetsVariables()` - Validates all required view variables are set
- `testAuthorizationSkipped()` - Confirms public pages don't require authentication

### Fixtures Used

All tests use comprehensive fixtures to simulate database state:

- `Sports` (legacy/backcompat — tests have been updated to prefer `sport_key` where applicable), `Teams`, `Seasons`, `TeamSeasons`
- `Persons`, `TeamSeasonRosters`
- `Games`, `GameTypes`, `Opponents`, `Places`, `Sites`
- `Images`, `ImageTags`, `ImagesImageTags`
- `BlogPosts`, `BlogTags`, `BlogPostsBlogTags`
- `StatBasketGamePerson`, `StatBasketGameBox`, `StatBasketGameTeam`, `StatBasketGameOpponent`
- `StatBasketSeasonPerson`

### Running PHP Tests

```bash
# Run all controller tests
php vendor/bin/phpunit tests/TestCase/Controller/SeasonsControllerTest.php \
                         tests/TestCase/Controller/PeopleControllerTest.php \
                         tests/TestCase/Controller/GamesControllerTest.php \
                         tests/TestCase/Controller/StatsControllerTest.php

# Run single test file
php vendor/bin/phpunit tests/TestCase/Controller/SeasonsControllerTest.php

# Run with coverage
php vendor/bin/phpunit --configuration phpunit.ci.xml \
                        --coverage-clover=coverage.xml \
                        tests/TestCase/Controller/
```

## JavaScript Tests

### Test Files Created

1. **turbo-navigation.test.js** - Tests for Turbo Drive navigation features
2. **public-pages.test.js** - Tests for public page UI components

### Turbo Navigation Tests

Tests cover core Turbo Drive functionality:

- **Basic Navigation**:
  - `turbo:load` event handling
  - `turbo:before-visit` event handling
  - `turbo:visit` event handling

- **Frame Loading**:
  - `turbo:frame-load` event handling
  - Finding turbo-frame elements
  - Frame loading state management

- **Error Handling**:
  - `turbo:frame-missing` events
  - `turbo:fetch-request-error` events

- **Cache Management**:
  - Clearing Turbo cache
  - Preventing caching on specific pages

- **Progress Bar**:
  - Showing progress on navigation
  - Hiding progress after load

- **Link Navigation**:
  - Intercepting link clicks
  - Skipping external links
  - Handling target="_blank" links

- **Form Submission**:
  - `turbo:submit-start` events
  - `turbo:submit-end` events

- **Scroll Restoration**:
  - Restoring scroll position
  - Scrolling to top on new pages

### Public Pages Tests

Tests cover UI components for all public sections:

- **Seasons Page**:
  - Season cards rendering
  - Tab navigation (Games, Roster, Images, Stories)
  - Game results display with win/loss badges

- **People Page**:
  - People table rendering
  - Profile tabs (Seasons, Stats, Images, Stories)

- **Games Page**:
  - Games table rendering
  - Box score display with player statistics

- **Stats Page**:
  - Season selector list
  - Statistics tables with percentages
  - Per-game averages display

- **Image Display**:
  - Image cards rendering
  - Image loading error handling

- **Blog Post Display**:
  - Blog post cards
  - "Read More" links

- **Responsive Behavior**:
  - Responsive column classes
  - Mobile-friendly table wrappers

### Running JavaScript Tests

```bash
# Run all JS tests
npx jest js/tests/

# Run specific test files
npx jest js/tests/legacy/turbo-navigation.test.js
npx jest js/tests/legacy/public-pages.test.js

# Run with coverage
npx jest --coverage

# Run in watch mode
npx jest --watch
```

## Branch Coverage Tests

The following test files were added to systematically cover branching logic and edge cases:

### New Test Files (159 suites total)

1. **admin_runtime.uncovered-branches.test.js** - Tests for admin runtime initialization edge cases, theme enforcement, and bootstrap lifecycle
2. **branch-coverage-targeting.test.js** - General branch coverage for global initialization states and window flag handling
3. **admin_dashboard_controller.test.js** - Stimulus controller tests for cache clear confirmation dialog with missing button target handling
4. **admin_users_index_controller.test.js** - Bulk user actions with DataTable integration, handles rows with missing cells
5. **[6 route/controller tests]** - Stimulus lifecycle tests for game_view, person_game_log, season_view, box_totals_toggle, team_season_image, back_navigation controllers
6. **pwa-installability.spec.js** - E2E tests for PWA installability with graceful CI handling

### Coverage Metrics (80% Branch Target)

- **Before**: 79.40% branches (3534/4515)
- **After**: 80% branches (3612/4515) - **TARGET REACHED**
- 1578 total tests across 159 suites
- 88.37% statements, 87.37% functions, 88.72% lines

### Key Testing Patterns

**Stimulus Controller Testing**:
```javascript
application.start();
application.register("controller-name", ControllerClass);
const root = document.querySelector('[data-controller="controller-name"]');
const controller = application.getControllerForElementAndIdentifier(root, "controller-name");
```

**Turbo Navigation Mock**:
```javascript
const originalTurbo = window.Turbo;
const visit = jest.fn();
window.Turbo = { visit };
// Restore the prior Turbo object in afterEach.
```

**Event Target Testing** (handling missing/non-Element targets):
```javascript
const event = new Event("turbo:frame-load");
Object.defineProperty(event, "target", { value: frame });
document.dispatchEvent(event);
```

## Architecture and Lifecycle Conventions

### PHP ownership and dependency injection

- Controllers adapt HTTP requests: validate request shape, authorize, call a service, and render, redirect, or serialize its result.
- Services own cross-table workflows and view-model/data transformations; reusable ORM queries belong in typed Table finders.
- Register service dependencies and typed tables in `Application::services()` and inject them through constructors. Avoid constructing services or resolving tables with `TableRegistry` inside request workflows.
- Tests should inject collaborators directly. Use a test factory helper when a workflow requires a configured context such as image or blog-post tagging.

### Stimulus and Turbo lifecycle

- Use Stimulus Targets and Values for controller-owned DOM and configuration; avoid global selectors for elements inside the controller root.
- Pair manually registered listeners, timers, observers, and requests with cleanup in `disconnect()`. Abort in-flight work and clear ephemeral UI state before Turbo caches a page, while preserving submitted values and server-rendered selections.
- Prefer Bootstrap `data-bs-*` attributes for static components. Keep imperative Bootstrap calls isolated to asynchronous flows or dynamically fetched modal fragments that cannot use static markup.

### Vite assets and forms

- Reference stylesheets through CakePHP's asset helpers or import them through Vite; import referenced images/fonts through Vite when they need manifest-backed URLs.
- Prefer selective package imports and the existing route modules to avoid loading duplicate or monolithic bundles.
- Social-link fields are newline-delimited URLs with user-facing help text and an example; never ask administrators to enter JSON.

## Test Coverage Goals

Per project requirements in `codecov.yml`:

- **PHP Coverage**: ≥ 98% (minimum 80% for new code)
- **JavaScript Coverage**: ≥ 88% statements, ≥ 80% branches (minimum 80% for new code)
- **Branch Coverage**: ≥ 80%

## Debugging Test Failures

### Common Issues

1. **Missing Column Names**: Check actual database schema vs. query columns
   - Example: `roster_number` not `jersey_number`
   - Example: `first`/`last` not `first_name`/`last_name`

2. **Invalid Associations**: Verify association names in Table classes
   - Example: `TeamSeason` (singular) not `TeamSeasons` (plural)
   - Example: TeamSeasons doesn't have `Sports` association

3. **Sport Filtering**: Use correct sport identification (prefer `sport_key`)
  - Prefer `sport_key` as the canonical runtime identifier. Use legacy `sport_id` only when interacting with older fixtures or migration snapshots.
  - Example: Prefer `sport_key` values provided by `SiteOptionsService::getAvailableSports()`; migration tooling maps legacy `sport_id` → `sport_key` where necessary.

### Test Environment

- Tests use SQLite in-memory database
- Fixtures provide consistent test data
- Integration tests simulate full HTTP request/response cycle
- Jest uses jsdom environment for DOM manipulation

## Continuous Integration

Tests run automatically via GitHub Actions on:

- Pull request creation
- Commits to `v-0.2.0.beta` and `v-1.0.dev`
- Manual workflow dispatch

Both PHP and JavaScript test suites must pass before code can be merged.

Coverage targets enforced via `codecov.yml`:
- PHP: 98%
- JS: 88%
- Branches: 80%

## References

- CakePHP Testing Guide: https://book.cakephp.org/5/en/development/testing.html
- Jest Documentation: https://jestjs.io/
- Hotwire Turbo: https://turbo.hotwired.dev/
- Project Copilot Instructions: `/.github/copilot-instructions.md`
