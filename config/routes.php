<?php

/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    // Use DashedRoute globally so dashed URLs inflect to CamelCase controllers/actions
    $routes->setRouteClass(DashedRoute::class);

    $routes->prefix('Admin', function (RouteBuilder $routes) {
        // All routes here will be prefixed with `/admin`
        // And have the 'Admin' prefix.
        // Make sure to call `parent::beforeFilter()` in your controllers.
        // AdminAuthMiddleware is handled globally in Application.php - no need to apply it here

        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index']); // Admin root route
        $routes->connect('/images/serve/:id', ['controller' => 'Images', 'action' => 'serve'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);

        // Explicit bulk upload route
        $routes->connect('/images/bulk-upload', ['controller' => 'Images', 'action' => 'bulkUpload']);

        // Blog admin shortcut
        $routes->connect('/blog', ['controller' => 'Blog', 'action' => 'index']);

        // Sport stats filterable index: /admin/sport-stats/1
        $routes->connect('/sport-stats/{sportId}', ['controller' => 'SportStats', 'action' => 'index'])
            ->setPass(['sportId'])
            ->setPatterns(['sportId' => '\\d+']);

        // Explicit route for AJAX sport-form-data endpoint (query param only)
        $routes->connect('/games/sport-form-data', [
            'controller' => 'Games',
            'action' => 'ajaxGameEavMeta',
        ]);

        $routes->fallbacks(DashedRoute::class);
    });

    // Public JSON API (no auth for now)
    $routes->prefix('Api', function (RouteBuilder $routes) {
        $routes->prefix('V1', function (RouteBuilder $routes) {
            $routes->setExtensions(['json']);

            // Health
            $routes->connect('/health', ['controller' => 'Health', 'action' => 'index']);

            // Persons
            $routes->connect('/persons', ['controller' => 'Persons', 'action' => 'index']);
            $routes->connect('/persons/{id}', ['controller' => 'Persons', 'action' => 'view'])
                ->setPass(['id'])
                ->setPatterns(['id' => '\\d+']);

            // Team seasons
            $routes->connect('/team-seasons', ['controller' => 'TeamSeasons', 'action' => 'index']);
            $routes->connect('/team-seasons/{id}', ['controller' => 'TeamSeasons', 'action' => 'view'])
                ->setPass(['id'])
                ->setPatterns(['id' => '\\d+']);

            // Games
            $routes->connect('/games', ['controller' => 'Games', 'action' => 'index']);
            $routes->connect('/games/{id}', ['controller' => 'Games', 'action' => 'view'])
                ->setPass(['id'])
                ->setPatterns(['id' => '\\d+']);

            // Basketball stats (read-only)
            $routes->connect('/basketball-stats/games/{gameId}', ['controller' => 'BasketballStats', 'action' => 'game'])
                ->setPass(['gameId'])
                ->setPatterns(['gameId' => '\\d+']);
            $routes->connect('/basketball-stats/team-seasons/{teamSeasonId}', ['controller' => 'BasketballStats', 'action' => 'season'])
                ->setPass(['teamSeasonId'])
                ->setPatterns(['teamSeasonId' => '\\d+']);

            // Blog posts (published)
            $routes->connect('/blog-posts', ['controller' => 'BlogPosts', 'action' => 'index']);
            $routes->connect('/blog-posts/{slug}', ['controller' => 'BlogPosts', 'action' => 'view'])
                ->setPass(['slug'])
                ->setPatterns(['slug' => '[a-zA-Z0-9_-]+' ]);
        });
    });
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->scope('/', function (RouteBuilder $builder): void {
        // CakeDC/Users (public auth UI)
        // Intentionally connect only auth-related endpoints (no public CRUD).
        $builder->connect('/login', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'login']);
        $builder->connect('/logout', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/register', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'register']);

        // Backwards-compatible URLs (the app historically used /users/*)
        $builder->connect('/users/login', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'login']);
        $builder->connect('/users/logout', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/users/register', ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'register']);

        // Deployment audit (read-only browser check — token-gated in production)
        $builder->connect('/install', ['controller' => 'Install', 'action' => 'index']);

        // Public image serve (unauthenticated)
        $builder->connect('/images/serve/:id', ['controller' => 'Images', 'action' => 'serve'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\\d+']);

        // Public server-rendered blog (Hotwire-enhanced)
        $builder->connect('/', ['controller' => 'Blog', 'action' => 'index']);
        $builder->connect('/blog', ['controller' => 'Blog', 'action' => 'index']);
        $builder->connect('/blog/{slug}', ['controller' => 'Blog', 'action' => 'view'])
            ->setPass(['slug'])
            ->setPatterns(['slug' => '[a-zA-Z0-9_-]+']);

        // Public frontend sections (Men's Basketball)
        $builder->connect('/seasons', ['controller' => 'Seasons', 'action' => 'index']);
        $builder->connect('/seasons/splits', ['controller' => 'Seasons', 'action' => 'splits']);
        $builder->connect('/seasons/{id}', ['controller' => 'Seasons', 'action' => 'view'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\\d+']);

        $builder->connect('/people', ['controller' => 'People', 'action' => 'index']);
        $builder->connect('/people/{id}', ['controller' => 'People', 'action' => 'view'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\\d+']);

        $builder->connect('/stats', ['controller' => 'Stats', 'action' => 'index']);
        $builder->connect('/stats/player-season', ['controller' => 'Stats', 'action' => 'playerSeason']);
        $builder->connect('/stats/team-season', ['controller' => 'Stats', 'action' => 'teamSeason']);
        $builder->connect('/stats/team-season-opponent', ['controller' => 'Stats', 'action' => 'teamSeasonOpponent']);
        $builder->connect('/stats/player-career', ['controller' => 'Stats', 'action' => 'playerCareer']);
        $builder->connect('/stats/player-game', ['controller' => 'Stats', 'action' => 'playerGame']);
        $builder->connect('/stats/team-game', ['controller' => 'Stats', 'action' => 'teamGame']);
        $builder->connect('/stats/opponent-player-game', ['controller' => 'Stats', 'action' => 'opponentPlayerGame']);
        $builder->connect('/stats/season/{teamSeasonId}', ['controller' => 'Stats', 'action' => 'season'])
            ->setPass(['teamSeasonId'])
            ->setPatterns(['teamSeasonId' => '\\d+']);

        $builder->connect('/games', ['controller' => 'Games', 'action' => 'index']);
        $builder->connect('/games/all', ['controller' => 'Games', 'action' => 'all']);
        $builder->connect('/games/ranked', ['controller' => 'Games', 'action' => 'ranked']);
        $builder->connect('/games/overtime', ['controller' => 'Games', 'action' => 'overtime']);
        $builder->connect('/games/hundred-point', ['controller' => 'Games', 'action' => 'hundredPoint']);
        $builder->connect('/games/openers', ['controller' => 'Games', 'action' => 'openers']);
        $builder->connect('/games/streaks', ['controller' => 'Games', 'action' => 'streaks']);
        $builder->connect('/games/margins', ['controller' => 'Games', 'action' => 'margins']);
        $builder->connect('/games/series', ['controller' => 'Games', 'action' => 'series']);
        $builder->connect('/games/stats/{id}', ['controller' => 'Games', 'action' => 'stats'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\\d+']);
        $builder->connect('/games/{id}', ['controller' => 'Games', 'action' => 'view'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\\d+']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();

        // Custom 404 route for any unmatched routes
        $builder->connect('/*', ['controller' => 'Error', 'action' => 'error404']);
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
