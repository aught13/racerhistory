# RacerHistory Web Application

[![Version](https://img.shields.io/badge/Version-0.1.0--alpha-orange.svg)](CHANGELOG.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![CakePHP](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Build Status](https://github.com/aught13/racerhistory/workflows/CI/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/ci.yml)
[![Security Scan](https://github.com/aught13/racerhistory/workflows/Security/badge.svg)](https://github.com/aught13/racerhistory/actions/workflows/security.yml)
[![Codecov](https://codecov.io/gh/aught13/racerhistory/branch/master/graph/badge.svg?token=)](https://app.codecov.io/gh/aught13/racerhistory)
[![GitHub last commit](https://img.shields.io/github/last-commit/aught13/racerhistory)](https://github.com/aught13/racerhistory)
[![GitHub issues](https://img.shields.io/github/issues/aught13/racerhistory)](https://github.com/aught13/racerhistory/issues)
[![GitHub stars](https://img.shields.io/github/stars/aught13/racerhistory)](https://github.com/aught13/racerhistory/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/aught13/racerhistory)](https://github.com/aught13/racerhistory/network)
[![Documentation](https://img.shields.io/badge/Documentation-95%25-brightgreen.svg)](templates/README.md)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-green.svg)](https://github.com/aught13/racerhistory/graphs/commit-activity)

A comprehensive web application for [racerhistory.com](https://racerhistory.com) built on CakePHP 5.x framework with Bootstrap 5.3.2 and modern web technologies.

## 🏁 Overview

This project powers the racerhistory.com website, providing features for:

- **User Authentication** - Secure registration, login, and password management
- **Admin Dashboard** - Administrative interface for user and content management
- **Historical Game Data** - Management system for sports historical data
- **Responsive Design** - Mobile-first Bootstrap 5.3.2 interface

Built with [CakePHP](https://cakephp.org) 5.x framework for robust, scalable web development.

## 🚀 Features

### Authentication System

- User registration with validation
- Secure login with password visibility controls
- Password reset functionality
- Session management with CakePHP Authentication plugin

### Administrative Interface

- Role-based admin dashboard
- User management (add, edit, approve, manage)
- Sports management (add, edit, delete, bulk operations with configurable sport settings)
- Teams management (add, edit, delete, bulk operations with sport associations)
- Seasons & Team Seasons management (rich text preview/recap with TinyMCE, image upload & preview)
- Team Season Rosters management (add/edit/delete, bulk delete, inline roster DataTable)
- **Games Management** (add/edit/delete, bulk operations, sport-aware period/official tracking)
- Dynamic Person AJAX search & inline person creation modal (roster forms)
- Responsive admin navigation with Bootstrap styling
- Comprehensive CRUD and bulk operations for all admin entities

### Game Management System

- **Smart Game Forms** - Sport-aware dynamic forms with AJAX loading of period/official fields
- **Business Rule Validation** - Games enforce season date ranges and future games can't have scores
- **Multi-Sport Support** - Basketball (halves/quarters), Football, Baseball with configurable periods/officials
- **EAV Attribute System** - Flexible storage for sport-specific game data (period scores, officials, attendance)
- **Cumulative Scoring** - Validates period totals match game totals for supported sports
- **Legacy Compatibility** - Maintains backward compatibility with existing game data

### UI/UX

- **Bootstrap 5.3.2** responsive framework
- **Bootstrap Icons 1.11.3** for consistent iconography
- **jQuery 3.7.1** for enhanced interactions
- Mobile-first responsive design
- Flash messaging system with multiple types

### Security Features

- CSRF protection on all forms
- HTML escaping for XSS prevention
- Password hashing with CakePHP security
- CDN integrity verification for external resources

## 📋 Requirements

- **PHP 8.1+** with required extensions
- **Composer** for dependency management
- **MySQL/MariaDB** database
- **Web server** (Apache/Nginx) or built-in PHP server

## 🔧 Installation

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
# Run migrations
bin/cake migrations migrate
```

### 4. Development Server

```bash
# Start built-in server
bin/cake server

# Or with custom host/port
bin/cake server --host 0.0.0.0 --port 8000
```

Visit `http://localhost:8765` (or your configured port) to see the application.

## 🏗️ Project Structure

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
│   └── View/               # View layer components
├── templates/              # View templates
│   ├── layout/             # Layout templates
│   ├── element/            # Reusable view elements
│   ├── Users/              # User-related views
│   └── Admin/              # Admin interface views
├── tests/                  # Unit and integration tests
└── webroot/                # Public web assets
```

## 📚 Documentation

### Application Documentation

- **[Templates Documentation](templates/README.md)** - Complete template system guide
- **[Source Code Documentation](src/)** - Comprehensive PHPDoc annotations
- **[Test Coverage](tests/)** - Unit and integration test suite

### Code Coverage & Test Suite

- **PHP Tests**: 288 tests / 761 assertions (controllers, models, integration, game flows & validation)
- **JavaScript Coverage** (Jest): Statements 88%+, Branches 80%+, Functions 95%+, Lines 90%+
- **Controllers**: High integration coverage (auth, CRUD, AJAX add/search, bulk, games management)
- **Models**: Validation & association tests (Sports, Teams, Seasons, TeamSeasons, Games, EAV, Persons, Images)
- **Business Rules**: Comprehensive validation testing (season dates, cumulative scoring, future games)
- **Frontend**: Jest exercises confirm-delete modal, image selector, sport-aware forms, error branches

### Key Documentation Features

- **PHPDoc Annotations**: All classes, methods, and properties documented
- **Template Headers**: Purpose, features, variables, and usage examples
- **Security Guidelines**: CSRF, XSS prevention, and authentication practices
- **Bootstrap Integration**: CDN integrity hashes and responsive design patterns

## 🧪 Testing

### Run Test Suites

```bash
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

## 🔐 Security

### Authentication

- **CakePHP Authentication Plugin** for secure user sessions
- **Password Hashing** using PHP's password_hash()
- **Form Security** with CSRF tokens on all forms

### Input Validation

- **HTML Escaping** via `h()` helper in all templates
- **Form Validation** with CakePHP validation rules
- **SQL Injection Prevention** through ORM and prepared statements

### External Resources

- **CDN Integrity Hashes** for Bootstrap and jQuery
- **HTTPS Enforcement** for production deployment
- **Secure Headers** for XSS and clickjacking protection

## 🎨 Frontend Technologies

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

## 🔄 Development Workflow

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

## 🚀 Deployment

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

## 🤝 Contributing

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

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- **Framework**: [CakePHP 5.x](https://cakephp.org)
- **CSS Framework**: [Bootstrap 5.3.2](https://getbootstrap.com)
- **Icons**: [Bootstrap Icons](https://icons.getbootstrap.com)
- **Testing**: [PHPUnit](https://phpunit.de)

---

### Built with ❤️ using CakePHP 5.x and Bootstrap**Built with ❤️ using CakePHP 5.x and Bootstrap**
