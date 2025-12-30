# RacerHistory Application Summary (v0.1.9-alpha)

RacerHistory is a CakePHP 5.2+ web application for managing historical sports data (sports, teams, seasons, team seasons, rosters, games, and sport-specific stats) plus a small blog engine. It includes an admin area for managing data and a public area for general viewing (including public blog pages and public image serving).

## Primary Modules

### 1) Authentication & Authorization

- Authentication uses the CakePHP Authentication plugin (session + form).
- Authorization uses the CakePHP Authorization plugin with policy checks.
- Admin access is gated at the request level (policies) and enforced in the admin base controller.
- User management (login/register/admin user CRUD) is implemented in-app (controllers + `UserManagerComponent`).
- The database schema includes CakeDC/Users-style fields (via migrations) for compatibility, but the CakeDC/Users plugin is not currently the primary auth UI.

Key files:
- `src/Application.php` (middleware + plugin wiring)
- `src/Policy/*` (policy rules)
- `src/Controller/UsersController.php`, `src/Controller/Admin/UsersController.php`

## 2) Admin Area (data management)

The admin area is served under the `/admin` prefix and provides CRUD + bulk operations for the core domain.

### Core entities managed in admin

- Sports, Teams
- Seasons, Team Seasons
- Team Season Rosters (with person lookup)
- Games (sport-aware dynamic forms)
- Places/Sites, Opponents, Game Types
- Images (upload/browse/select, tagging)
- Basketball game stats + basketball season stats (where enabled)
- Blog posts (draft/publish workflow)

Patterns used:
- Controllers orchestrate HTTP requests and delegate business rules to services.
- Service-layer classes (under `src/Service/`) encapsulate non-trivial domain logic and labeling for UI.

## 3) Sport-aware Games + EAV metadata

Games support multiple sports by using configurable sport settings and an EAV-style metadata system for sport-specific fields such as periods and officials.

Highlights:
- Dynamic admin game forms adapt based on the selected team season’s sport configuration.
- Business rules include season date-range validation and sport-aware scoring/period validation.

Key files:
- `src/Service/GameService.php`, `src/Service/SportConfigService.php`
- `src/Controller/Admin/GamesController.php`
- `webroot/js/sport-aware-game-form.js`, `webroot/js/games_sport_dynamic.js`

## 4) Basketball statistics

Basketball adds dedicated tracking for:
- Per-game box scores (team, players, opponents)
- Per-season totals (team, players, opponents)

The basketball box/period editing flows are separated into dedicated controllers and a service that assembles the data for game views.

Key files:
- `src/Service/BasketballStatsService.php`
- `src/Controller/Admin/StatBasketGameBoxController.php` and related StatBasket* controllers

## 5) Images (storage + variants + tagging)

Images are uploaded via admin endpoints, stored on disk under a public storage root, and are retrievable through a public `/images/serve/{id}` route.

Highlights:
- Centralized upload/storage logic via `ImageStorageService` and `ImageProcessor`.
- Variants are configured centrally (thumb/medium plus WebP outputs).
- Tags are first-class, and the tagging layer can attach structured tags (team, team season, roster, game, etc.) for filtering and reuse in the UI.

Key files:
- `src/Service/ImageStorageService.php`, `src/Service/ImageProcessor.php`, `src/Service/TaggingService.php`
- `src/Controller/ImagesController.php`, `src/Controller/Admin/ImagesController.php`
- More detail: `README_IMAGE_STORAGE.md`

## 6) Blog engine

Public blog:
- `/blog` shows published posts.
- `/blog/{slug}` shows a single published post.

Admin blog:
- `/admin/blog-posts` for list/add/edit/delete.
- Uses a shared edit template with TinyMCE and image selection for hero and inline images.
- Uses the same tagging infrastructure as images.

Key files:
- `src/Controller/BlogController.php`, `src/Controller/Admin/BlogPostsController.php`
- `src/Service/BlogPostService.php`
- `templates/Blog/*`, `templates/Admin/BlogPosts/*`

## Key Routes (high level)

Public:
- `/` (home)
- `/blog`, `/blog/{slug}`
- `/images/serve/{id}`

Admin:
- `/admin` (dashboard)
- `/admin/*` CRUD controllers (sports/teams/seasons/team-seasons/games/images/blog-posts/etc.)

Routes are defined in `config/routes.php`.

## Development Workflow

Common commands:
- PHP tests: `vendor/bin/phpunit`
- Static analysis: `php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- PHPCS: `php vendor/bin/phpcs --standard=phpcs.xml src/ tests/`
- JS lint: `npm run lint:js`
- JS tests: `npm run test:js`

VS Code tasks are provided for the above workflows.
