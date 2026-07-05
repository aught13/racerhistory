# RacerHistory Web Application

[![Version](https://img.shields.io/badge/Version-0.2.0--beta-blue.svg)](CHANGELOG.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![CakePHP](https://img.shields.io/badge/dynamic/json?url=https://raw.githubusercontent.com/aught13/racerhistory/v-1.0.dev/cakephp-version.json&query=$.version&label=CakePHP&color=red)](https://cakephp.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](composer.json)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-green.svg)](https://github.com/aught13/racerhistory/graphs/commit-activity)
[![GitHub last commit](https://img.shields.io/github/last-commit/aught13/racerhistory)](https://github.com/aught13/racerhistory)
[![GitHub issues](https://img.shields.io/github/issues/aught13/racerhistory)](https://github.com/aught13/racerhistory/issues)

[![Build Status](https://github.com/aught13/racerhistory/workflows/CI/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/ci.yml)
[![Security Scan](https://github.com/aught13/racerhistory/workflows/Security/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/security.yml)
<a href="https://phpstan.org/" target="_blank"><img alt="PHPStan" src="https://img.shields.io/badge/PHPStan-level%205-brightgreen?style=flat"></a>
<a href="https://github.com/squizlabs/PHP_CodeSniffer" target="_blank"><img alt="Code Consistency" src="https://img.shields.io/badge/PHPCS-passing-blue?style=flat"></a>
[![Codecov](https://codecov.io/gh/aught13/racerhistory/branch/v-1.0.dev/graph/badge.svg?token=)](https://app.codecov.io/gh/aught13/racerhistory)
[![Documentation](https://img.shields.io/badge/dynamic/json?url=https://raw.githubusercontent.com/aught13/racerhistory/v-1.0.dev/docs-status.json&query=$.documentationPercent&label=Documentation&color=brightgreen)](templates/README.md)

[![GitHub stars](https://img.shields.io/github/stars/aught13/racerhistory)](https://github.com/aught13/racerhistory/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/aught13/racerhistory)](https://github.com/aught13/racerhistory/network)



A comprehensive web application for [racerhistory.com](https://racerhistory.com) built on CakePHP 5.2+ with Bootstrap 5.3.2, an AdminLTE 4-based admin experience, and modern web tooling.

- Start here: [APPLICATION_SUMMARY.md](APPLICATION_SUMMARY.md)

## Overview

This project powers the racerhistory.com website, providing features for:

- **User Authentication** - Registration, login, logout, password reset
- **Admin Dashboard** - Administrative interface for site data management
- **Historical Game Data** - Sports/team/season/game management with sport-aware configuration
- **Blog Engine** - Public blog with an admin editor, tagging, and hero images
- **Responsive Design** - Mobile-first Bootstrap 5.3.2 public interface with an AdminLTE 4 admin shell

Built with [CakePHP](https://cakephp.org) 5.x framework for robust, scalable web development.

## Features

### Authentication & Authorization

- **Authentication**: CakePHP Authentication plugin (session + form)
- **Authorization**: CakePHP Authorization plugin with policy checks
- **Policy-based admin gating**: request-level authorization for `/admin/*`
- **Public auth UI**: CakeDC/Users (login/logout/password reset). Public registration is disabled.
- **Admin user management**: implemented in-app via `Admin/UsersController` (approve/edit users, roles)

### Administrative Interface

- **User Management** - Add, edit, approve users with first_name/last_name support
- **Policy-Based Authorization** - Fine-grained access control via UserPolicy
- Role-based admin dashboard
- Sports management (add, edit, delete, bulk operations with configurable sport settings)
- Teams management (add, edit, delete, bulk operations with sport associations)
- Seasons & Team Seasons management (rich text preview/recap with TinyMCE, image upload & preview)
- Team Season Rosters management (add/edit/delete, bulk delete, inline roster DataTable)
- **Games Management** (add/edit/delete, bulk operations, sport-aware period/official tracking)
- **Basketball Statistics** (player, opponent, and team stats with comprehensive tracking)
- Dynamic Person AJAX search & inline person creation modal (roster forms)
- AdminLTE 4 sidebar navigation with grouped treeview sections (Sports, Content)
- Persistent desktop sidebar collapse and mobile sidebar overlay behavior
- Comprehensive CRUD and bulk operations for all admin entities

### Game Management System

- **Smart Game Forms** - Sport-aware dynamic forms with AJAX loading of period/official fields
- **Business Rule Validation** - Games enforce season date ranges and future games can't have scores
- **Multi-Sport Support** - Basketball (halves/quarters), Football, Baseball with configurable periods/officials
- **EAV Attribute System** - Flexible storage for sport-specific game data (period scores, officials, attendance)
- **Cumulative Scoring** - Validates period totals match game totals for supported sports
- **Basketball Statistics** - Comprehensive player, opponent, and team statistics tracking
  - Player stats with team roster linkage (GP/GS tracking)
  - Opponent player stats with name-based tracking
  - Team-level stats (rebounds, turnovers, technical fouls)
  - Integrated display in game view with proper validation
  - Season totals update option (final box score) with aggregation into team & opponent season totals
  - Refactored architecture: dedicated `StatBasketGameBoxController` for game/period box management and `BasketballStatsService` for consolidated data loading; `GamesController` now minimal sport-aware orchestrator
- **Legacy Compatibility** - Maintains backward compatibility with existing game data

### Blog

- **Public blog**: Landing (`/`) hero layout, News (`/blog`) and individual post (`/blog/{slug}`) endpoints built upon standard WordPress-style grid rendering (incorporating Popular Tag navigation and custom chronologic feed widgets)
- **Admin editor**: `/admin/blog-posts` with draft/publish workflow
- **Rich editing**: TinyMCE integration with inline image uploads
- **Tagging**: reuse the same tag infrastructure as images, with freeform tags and structured tags (team/team-season/game/site/opponent/sport/person/roster)

### Images

- **Upload + browse** in admin
- **Public serving route**: `/images/serve/{id}`
- **Variants** configured centrally (see `Application::bootstrap()`): thumb/medium plus WebP outputs
- **Taggable** images via the tagging service layer

### UI/UX

- **Bootstrap 5.3.2** responsive framework
- **AdminLTE 4.0.2** admin dashboard/theme framework (built on Bootstrap)
- **Bootstrap Icons 1.11.3** for consistent iconography
- **Vite-managed frontend bundles** for public/runtime JavaScript, including jQuery, Bootstrap JS, Luxon, and DataTables extensions
- Mobile-first responsive design

### Security Features

- **Authorization Framework** - CakePHP Authorization plugin (policies) with request-level and entity-level checks
- **Identity Management** - Secure session handling with CakePHP Authentication
- CSRF protection on all forms
- HTML escaping for XSS prevention
- Password hashing with CakePHP security
- CDN integrity verification for external resources

## Requirements

- **PHP 8.2+** with required extensions
- **Composer** for PHP dependencies
- **MySQL/MariaDB** for production (tests typically use SQLite unless configured otherwise)
- **Node.js 20+** (recommended) for JS linting/tests

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/aught13/racerhistory.git
cd racerhistory
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Database Setup

```bash
# Copy configuration template
cp config/app_local.example.php config/app_local.php

# Edit config/app_local.php with your database credentials
# Run migrations (includes CakeDC/Users fields)
bin/cake migrations migrate
```

**Note**: The migrations add enhanced user fields (first_name, last_name, active, is_superuser, last_login, activation_date) for CakeDC/Users compatibility.

### 4. Development Server

```bash
# Start built-in server
bin/cake server

# Or with custom host/port
bin/cake server --host 0.0.0.0 --port 8000
```

Visit `http://localhost:8765` (or your configured port) to see the application.

## Project Structure

```
racerhistory/
├── bin/                    # CLI tools (cake, deploy, fix-permissions)
├── config/                 # Configuration files
│   ├── Migrations/         # Database migrations
│   ├── importmap.php       # ES module import mapping (Hotwire)
│   └── app_local.php       # Local environment config (not tracked)
├── src/                    # Application source code
│   ├── Controller/         # Request handlers
│   │   ├── Admin/          # Admin-specific controllers
│   │   └── Component/      # Reusable controller components
│   ├── Model/              # Data layer
│   │   ├── Entity/         # Data entities
│   │   └── Table/          # Database tables
│   ├── Policy/             # Authorization policies
│   ├── Service/            # Business logic layer (31 services)
│   └── View/               # View layer components
├── templates/              # View templates
│   ├── layout/             # Layout templates (default + admin)
│   ├── element/            # Reusable view elements
│   ├── Admin/              # Admin interface views
│   ├── Blog/               # Public blog views
│   └── Games/, People/...  # Public domain views
├── tests/                  # PHPUnit tests (1235 tests)
└── webroot/                # Public web assets
    ├── js/                 # JavaScript (ES modules + tests)
    └── css/                # Stylesheets
```

## Documentation & Architecture

### Application Documentation

- **[Application Summary](APPLICATION_SUMMARY.md)** - What the app does and how modules fit together
- **[Authorization Setup Guide](AUTHORIZATION_SETUP.md)** - Authentication/authorization design and policy patterns
- **[Image Storage Guide](README_IMAGE_STORAGE.md)** - Image upload, variants, storage layout, tagging
- **[Templates Documentation](templates/README.md)** - Template structure and UI element conventions

### Testing & Quality

- **PHP**: PHPUnit (1235 tests / 4056 assertions), PHPStan (0 errors), PHPCS (clean)
- **JavaScript**: Jest (1112 tests across 125 suites; latest local coverage about 89.73% statements, 81.97% branches), ESLint, Prettier
- **E2E**: Playwright coverage for Turbo/admin flows including sidebar group expansion behavior
- **Coverage Targets**: PHP 98%, JS 88%, branches 80% (enforced via Codecov)
- VS Code tasks exist for common workflows (PHPUnit/PHPCS/PHPStan/Jest)

### Key Documentation Features

- **PHPDoc Annotations**: All classes, methods, and properties documented
- **Template Headers**: Purpose, features, variables, and usage examples
- **Security Guidelines**: CSRF, XSS prevention, and authentication practices
- **Bootstrap Integration**: CDN integrity hashes and responsive design patterns

## Testing

### Run Test Suites

```bash
# PHP tests (1235 tests, 4056 assertions)
vendor/bin/phpunit

# JavaScript tests with coverage (1112 tests, 125 suites)
npm run test:js

# PHP coverage report (CI-style)
vendor/bin/phpunit --configuration phpunit.ci.xml

# Static analysis
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G

# Code style
vendor/bin/phpcs --standard=phpcs.xml src/ tests/
```

### Test Structure

- **Unit Tests**: Model validation, entity behavior, component logic
- **Integration Tests**: Controller actions, authentication, form processing
- **Fixtures**: Test data for consistent testing environment

## Security & Authorization

### Authentication & Authorization

- **CakePHP Authentication** (session + form) and **CakePHP Authorization** (policies)
- Policies cover both request-level checks (admin access) and entity-level checks (user operations)
- CSRF protection enabled globally

### Authorization Features

- **Policy Classes**: UserPolicy, ApplicationPolicy, RequestPolicy
- **Resource-Based Permissions**: Users can only edit their own data
- **Admin Override**: Superuser role bypasses standard restrictions
- **Public Access Control**: Explicit authorization skipping for public pages

### Input Validation

- **HTML Escaping** via `h()` helper in all templates
- **Form Validation** with CakePHP validation rules
- **SQL Injection Prevention** through ORM and prepared statements

### External Resources

- **CDN Integrity Hashes** for externally hosted CSS assets
- **Vite manifest-backed JavaScript bundles** for public/runtime JS dependencies
- **HTTPS Enforcement** for production deployment
- **Secure Headers** for XSS and clickjacking protection

## Frontend Technologies

### CSS Framework

- **Bootstrap 5.3.2** - Responsive CSS framework
- **Bootstrap Icons 1.11.3** - Comprehensive icon library
- **Custom Styling** - Additional cake.css for application-specific styles

### JavaScript

- **Hotwire Turbo 8.x** - SPA-like navigation without full page reloads
- **Hotwire Stimulus 3.x** - Modest JavaScript framework for progressive enhancement
- **Vite-managed ES modules** - `js/main.js` with split public/admin/runtime bundles
- **jQuery 3.7.1** - npm-managed for DataTables-dependent public/admin behavior
- **Bootstrap JS** - npm-managed bundle for modals, dropdowns, and components
- **DataTables + extensions** - npm-managed public bundles for SearchBuilder, Scroller, Responsive, Buttons, and DateTime/Luxon integration
- **Sport-Aware Forms**: `games_sport_dynamic.js`, `sport-aware-game-form.js` - Dynamic form fields based on sport configuration
- **TinyMCE** - Rich text editor for blog posts and team season content
- **Tests**: Jest + jsdom with 90%+ statement coverage

### Design Principles

- **Mobile-First** responsive design
- **Accessibility** with ARIA labels and semantic HTML
- **Performance** optimized with manifest-backed Vite bundles and minified assets

## Development Workflow

### Code Standards

- **PSR-12** PHP coding standards
- **CakePHP Conventions** for naming and structure
- **PHPDoc Documentation** for all public methods
- **Unit Tests** for new functionality

### Tools

- **PHPStan** - Static analysis
- **PHP_CodeSniffer** - Code style checking
- **PHPUnit** - Testing framework
- **Composer** - Dependency management

## Deployment

### Production Deployment

Use the included deploy script to audit and set up a production environment:

```bash
# Audit current state (no changes made)
bin/deploy.sh --check-only

# Full deployment (install deps, run migrations, clear caches)
bin/deploy.sh

# Deploy without running tests
bin/deploy.sh --skip-tests
```

The deploy script checks:
- PHP version and required extensions
- Configuration (debug mode, security salt, database host)
- Directory permissions (tmp, logs, storage)
- Dependency installation (`composer install --no-dev`)
- Database migration status
- Security (no debug files exposed, no credentials in webroot)
- Frontend asset presence

### Manual Setup

1. Configure `config/app_local.php` for production database
2. Set `'debug' => false` in configuration (or `DEBUG=false` in environment)
3. Run `composer install --no-dev --optimize-autoloader`
4. Run `bin/cake migrations migrate`
5. Configure web server with proper document root (`webroot/`)
6. Set up SSL certificate for HTTPS
7. Run `bin/fix-permissions.sh` to set proper file ownership and permissions

### Environment Configuration

- **Development**: Debug enabled, detailed error reporting
- **Production**: Debug disabled, error logging, optimized autoloader

## Contributing

### Code Contributions

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Follow coding standards and add tests
4. Update documentation for new features
5. Submit pull request with detailed description

### Documentation Updates

- All new features require documentation updates
- Template changes need header documentation
- API changes require PHPDoc updates

## License

This project is licensed under the MIT License. The license declaration is in `composer.json`.

## 🔗 Links

- **Framework**: [CakePHP 5.x](https://cakephp.org)
- **CSS Framework**: [Bootstrap 5.3.2](https://getbootstrap.com)
- **Icons**: [Bootstrap Icons](https://icons.getbootstrap.com)
- **Testing**: [PHPUnit](https://phpunit.de)

---

### Built with CakePHP 5.x and Bootstrap
