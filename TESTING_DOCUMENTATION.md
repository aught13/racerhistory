# Testing Documentation (v0.2.0-beta)

## Overview

Comprehensive test coverage for the RacerHistory application spanning PHP backend and JavaScript frontend.

**Current Statistics:**
- **PHPUnit**: 983 tests, 2799 assertions (4 skipped)
- **Jest**: 789 tests across 110 suites
- **JS Coverage**: 91% statements, 84% branches, 89% functions
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

- `Sports`, `Teams`, `Seasons`, `TeamSeasons`
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
npx jest webroot/js/tests/

# Run specific test files
npx jest webroot/js/tests/turbo-navigation.test.js
npx jest webroot/js/tests/public-pages.test.js

# Run with coverage
npx jest --coverage

# Run in watch mode
npx jest --watch
```

## Test Coverage Goals

Per project requirements in `codecov.yml`:

- **PHP Coverage**: ≥ 98% (minimum 80% for new code)
- **JavaScript Coverage**: ≥ 88% (minimum 80% for new code)
- **Branch Coverage**: ≥ 80%

## Debugging Test Failures

### Common Issues

1. **Missing Column Names**: Check actual database schema vs. query columns
   - Example: `roster_number` not `jersey_number`
   - Example: `first`/`last` not `first_name`/`last_name`

2. **Invalid Associations**: Verify association names in Table classes
   - Example: `TeamSeason` (singular) not `TeamSeasons` (plural)
   - Example: TeamSeasons doesn't have `Sports` association

3. **Sport Filtering**: Use correct sport identification
   - Use `sport_id` or combination of `sport_name` + `gender` field
   - Example: Men's Basketball is `sport_name='Basketball'` + `gender='M'`

### Test Environment

- Tests use SQLite in-memory database
- Fixtures provide consistent test data
- Integration tests simulate full HTTP request/response cycle
- Jest uses jsdom environment for DOM manipulation

## Continuous Integration

Tests run automatically via GitHub Actions on:

- Pull request creation
- Commits to main branch
- Manual workflow dispatch

Both PHP and JavaScript test suites must pass before code can be merged.

Coverage targets enforced via `codecov.yml`:
- PHP: 98%
- JS: 88%
- Branches: 80%
5. Add performance benchmarks

## References

- CakePHP Testing Guide: https://book.cakephp.org/5/en/development/testing.html
- Jest Documentation: https://jestjs.io/
- Hotwire Turbo: https://turbo.hotwired.dev/
- Project Copilot Instructions: `/.github/copilot-instructions.md`
