# GitHub CI Workflow Overview

This document tracks the current behavior of `.github/workflows/ci.yml`.

## Triggers

- Push and pull request events on `v-0.2.0.beta` and `v-1.0.dev`
- Manual dispatch (`workflow_dispatch`)

## Concurrency

The workflow uses a top-level concurrency group:

```yaml
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true
```

When a new commit is pushed to the same branch, older in-progress runs are canceled.

## Job Planning and Fail-Fast Behavior

`preflight` is the planning job and publishes outputs used to gate expensive jobs:

- `run_php`
- `run_js`
- `run_e2e`

Downstream jobs use those outputs with `needs: preflight` and job-level `if:` conditions. This provides fail-fast behavior by skipping unrelated work based on changed files.

## Current Jobs

1. `preflight`
- Calculates changed-file impact and sets output flags.

2. `testsuite`
- PHP matrix tests (PHP 8.1/8.2/8.3) with MySQL service.
- Uploads PHP coverage for the coverage-enabled matrix leg.

3. `coding-standard`
- Runs PHPCS and PHPStan.

4. `js-tests`
- Runs ESLint, Prettier check, Jest coverage, and CSS lint.
- Uploads JS coverage artifact and reports to Codecov.

5. `e2e-tests`
- Runs Playwright end-to-end tests against a live CakePHP server.
- Uses native Playwright sharding via matrix strategy.

## Playwright Sharding

`e2e-tests` is sharded in parallel on GitHub runners:

```yaml
strategy:
  fail-fast: false
  matrix:
    shard_index: [1, 2, 3]
    shard_total: [3]
```

Each shard runs:

```bash
npm run test:e2e -- --shard=${{ matrix.shard_index }}/${{ matrix.shard_total }}
```

Shard-aware artifact names are used to avoid collisions:

- `playwright-results-<shard>-of-<total>`
- `playwright-report-<shard>-of-<total>`
- `playwright-traces-<shard>-of-<total>`

## Local Reproduction

```bash
# JS quality + tests
npm run lint:js
npm run format:js:check
npm run test:js -- --ci --coverage

# E2E (with app running on 8765)
PLAYWRIGHT_BASE_URL=http://localhost:8765 npm run test:e2e
```

## Notes

- Coverage thresholds are controlled in `codecov.yml`.
- If CI behavior changes, update this file together with `.github/workflows/ci.yml`.
```

---

**Last Updated**: 2026-03-01
**Status**: ✅ Ready for production use
