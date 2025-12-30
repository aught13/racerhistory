# RacerHistory Web Application

[![Version](https://img.shields.io/badge/Version-0.1.9--alpha-orange.svg)](CHANGELOG.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![CakePHP](https://img.shields.io/badge/dynamic/json?url=https://raw.githubusercontent.com/aught13/racerhistory/master/cakephp-version.json&query=$.version&label=CakePHP&color=red)](https://cakephp.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-green.svg)](https://github.com/aught13/racerhistory/graphs/commit-activity)
[![GitHub last commit](https://img.shields.io/github/last-commit/aught13/racerhistory)](https://github.com/aught13/racerhistory)
[![GitHub issues](https://img.shields.io/github/issues/aught13/racerhistory)](https://github.com/aught13/racerhistory/issues)

[![Build Status](https://github.com/aught13/racerhistory/workflows/CI/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/ci.yml)
[![Security Scan](https://github.com/aught13/racerhistory/workflows/Security/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/security.yml)
<a href="https://phpstan.org/" target="_blank"><img alt="PHPStan" src="https://img.shields.io/badge/dynamic/yaml?url=https://raw.githubusercontent.com/aught13/racerhistory/master/phpstan.neon&query=$.parameters.level&label=PHPStan&prefix=level%20&color=brightgreen&style=flat"></a>
<a href="https://github.com/squizlabs/PHP_CodeSniffer" target="_blank"><img alt="Code Consistency" src="https://img.shields.io/badge/dynamic/json?url=https://raw.githubusercontent.com/aught13/racerhistory/master/phpcs-status.json&query=$.status&label=PHPCS&color=blue&style=flat"></a>
[![Codecov](https://codecov.io/gh/aught13/racerhistory/branch/master/graph/badge.svg?token=)](https://app.codecov.io/gh/aught13/racerhistory)
[![Documentation](https://img.shields.io/badge/dynamic/json?url=https://raw.githubusercontent.com/aught13/racerhistory/master/docs-status.json&query=$.documentationPercent&label=Documentation&color=brightgreen)](templates/README.md)

[![GitHub stars](https://img.shields.io/github/stars/aught13/racerhistory)](https://github.com/aught13/racerhistory/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/aught13/racerhistory)](https://github.com/aught13/racerhistory/network)



A comprehensive web application for [racerhistory.com](https://racerhistory.com) built on CakePHP 5.2+ with Bootstrap 5.3.2 and modern web tooling.

- Start here: [APPLICATION_SUMMARY.md](APPLICATION_SUMMARY.md)

## Overview

This project powers the racerhistory.com website, providing features for:

- **User Authentication** - Registration, login, logout, password reset
- **Admin Dashboard** - Administrative interface for site data management
- **Historical Game Data** - Sports/team/season/game management with sport-aware configuration
- **Blog Engine** - Public blog with an admin editor, tagging, and hero images
- **Responsive Design** - Mobile-first Bootstrap 5.3.2 interface

Built with [CakePHP](https://cakephp.org) 5.x framework for robust, scalable web development.

## Features

### Authentication & Authorization

- **Authentication**: CakePHP Authentication plugin (session + form)
- **Authorization**: CakePHP Authorization plugin with policy checks
- **Policy-based admin gating**: request-level authorization for `/admin/*`
- **User management**: implemented in-app via `UsersController`, `Admin/UsersController`, and `UserManagerComponent`
- **Compatibility**: the schema includes CakeDC/Users-style fields (via migrations), but the CakeDC/Users plugin is not currently wired as the primary auth UI

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
- Responsive admin navigation with Bootstrap styling
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

- **Public blog**: `/blog` and `/blog/{slug}`
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
- **Bootstrap Icons 1.11.3** for consistent iconography
- **jQuery 3.7.1** for enhanced interactions
- Mobile-first responsive design

### Security Features

- **Authorization Framework** - CakePHP Authorization plugin (policies) with request-level and entity-level checks
- **Identity Management** - Secure session handling with CakePHP Authentication
- CSRF protection on all forms
- HTML escaping for XSS prevention
- Password hashing with CakePHP security
- CDN integrity verification for external resources

## Requirements

- **PHP 8.1+** with required extensions
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
├── config/                 # Configuration files
│   ├── Migrations/         # Database migrations
│   └── app_local.php       # Local environment config
├── src/                    # Application source code
│   ├── Controller/         # Request handlers
│   │   ├── Admin/          # Admin-specific controllers
│   │   └── Component/      # Reusable controller components
│   ├── Model/              # Data layer
│   │   ├── Entity/         # Data entities
│   │   └── Table/          # Database tables
│   ├── Policy/             # Authorization policies
│   │   ├── UserPolicy.php      # User resource permissions
│   │   ├── ApplicationPolicy.php  # Base policy
│   │   └── RequestPolicy.php   # Request-level authorization
│   └── View/               # View layer components
├── templates/              # View templates
│   ├── layout/             # Layout templates
│   ├── element/            # Reusable view elements
│   ├── Users/              # User-related views
│   └── Admin/              # Admin interface views
├── tests/                  # Unit and integration tests
└── webroot/                # Public web assets
```

## Documentation & Architecture

### Application Documentation

- **[Application Summary](APPLICATION_SUMMARY.md)** - What the app does and how modules fit together
- **[Authorization Setup Guide](AUTHORIZATION_SETUP.md)** - Authentication/authorization design and policy patterns
- **[Image Storage Guide](README_IMAGE_STORAGE.md)** - Image upload, variants, storage layout, tagging
- **[Templates Documentation](templates/README.md)** - Template structure and UI element conventions

### Testing & Quality

- **PHP**: PHPUnit, PHPStan, PHPCS
- **JavaScript**: ESLint, Prettier, Jest (coverage thresholds enforced in CI)
- VS Code tasks exist for common workflows (PHPUnit/PHPCS/PHPStan/Jest)

### Key Documentation Features

- **PHPDoc Annotations**: All classes, methods, and properties documented
- **Template Headers**: Purpose, features, variables, and usage examples
- **Security Guidelines**: CSRF, XSS prevention, and authentication practices
- **Bootstrap Integration**: CDN integrity hashes and responsive design patterns

## Testing

### Run Test Suites

```bash
# PHP tests (auto discovery)
vendor/bin/phpunit

# JavaScript tests with coverage
npm run test:js

# Optional PHP coverage config (CI-style)
vendor/bin/phpunit --configuration phpunit.ci.xml
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

- **CDN Integrity Hashes** for Bootstrap and jQuery
- **HTTPS Enforcement** for production deployment
- **Secure Headers** for XSS and clickjacking protection

## Frontend Technologies

### CSS Framework

- **Bootstrap 5.3.2** - Responsive CSS framework
- **Bootstrap Icons 1.11.3** - Comprehensive icon library
- **Custom Styling** - Additional cake.css for application-specific styles

### JavaScript

- **jQuery 3.7.1** - DOM manipulation and AJAX
- **Bootstrap JS** - Modals, dropdowns, components
- **Modules**: `admin.js` (confirm-delete + toast), `person-image.js` (image upload/preview), dynamic roster person search
- **Sport-Aware Forms**: `games_sport_dynamic.js`, `sport-aware-game-form.js` - Dynamic form fields based on sport configuration
- **TinyMCE** - Rich text fields for Team Seasons preview/recap
- **Tests**: Jest + jsdom with comprehensive coverage including edge cases and error paths

### Design Principles

- **Mobile-First** responsive design
- **Accessibility** with ARIA labels and semantic HTML
- **Performance** optimized with CDN resources and minified assets

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

### Production Setup

1. Configure `config/app_local.php` for production database
2. Set `'debug' => false` in configuration
3. Run `composer install --no-dev --optimize-autoloader`
4. Configure web server with proper document root (`webroot/`)
5. Set up SSL certificate for HTTPS
6. Configure caching and session storage

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

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- **Framework**: [CakePHP 5.x](https://cakephp.org)
- **CSS Framework**: [Bootstrap 5.3.2](https://getbootstrap.com)
- **Icons**: [Bootstrap Icons](https://icons.getbootstrap.com)
- **Testing**: [PHPUnit](https://phpunit.de)

---

### Built with CakePHP 5.x and Bootstrap
