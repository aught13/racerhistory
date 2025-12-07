<?php

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);

namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @extends \Cake\Http\BaseApplication<\App\Application>
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI !== 'cli') {
            // The bake plugin requires fallback table classes to work properly
            FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
        }

        // Load CakeDC/Auth for authorization policies only
        // We're not using the full Users plugin, just Auth for policies
        $this->addPlugin('CakeDC/Auth', [
            'bootstrap' => false, // Don't load their bootstrap config
            'routes' => false,
        ]);

        // Image variants configuration (central place)
        \Cake\Core\Configure::write('Images.variants', [
            'thumb' => ['fit' => [150,150]],
            'medium' => ['maxWidth' => 800],
        ]);
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
        // Catch any exceptions in the lower layers,
        // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance.
            // See https://github.com/CakeDC/cakephp-cached-routing
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]))

            // Add Authentication Middleware AFTER CSRF
            // This middleware will handle authentication and set the identity
            // on the request object.
            // It should be added before any middleware that requires authentication.
            ->add(new AuthenticationMiddleware($this))

            // Add Authorization Middleware AFTER Authentication
            // This checks permissions based on policies and injects the service into identity
            ->add(new AuthorizationMiddleware($this));

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Returns a configured authentication service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
    // NOTE Authentication config pattern:
    // We inline the identifier configuration inside the Form authenticator instead of
    // supplying a top-level 'identifiers' key. The Authentication plugin (>=3.3) deprecates
    // the implicit loadIdentifier() path that is triggered when global identifiers are
    // declared. Embedding the identifier array here prevents the deprecated method call
    // and keeps construction quiet during tests & runtime. If additional authenticators
    // need to share this identifier later, extract it to a variable and reference the
    // same array (still inline) rather than reinstating the global 'identifiers' option.
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => '/users/login',
            'queryParam' => 'redirect',
        ]);

        // Authenticators: session then form (inline identifier config to avoid deprecated loadIdentifier()).
        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => 'Authentication.Password',
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'loginUrl' => '/users/login',
        ]);

        return $service;
    }

    /**
     * Returns a configured authorization service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        // Use a collection of resolvers
        $mapResolver = new MapResolver();

        // Map concrete ServerRequest class to RequestPolicy (not the interface)
        $mapResolver->map(\Cake\Http\ServerRequest::class, \App\Policy\RequestPolicy::class);

        // Create resolver collection with both map and ORM resolvers
        $resolvers = new ResolverCollection([
            $mapResolver,
            new OrmResolver(),
        ]);

        $service = new AuthorizationService($resolvers);

        return $service;
    }
}
