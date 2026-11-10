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

use App\Controller\Admin\BasketballBoxScoreImportController;
use App\Controller\Admin\BlogPostsController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\GamesController as AdminGamesController;
use App\Controller\Admin\GameTypesController;
use App\Controller\Admin\ImagesController as AdminImagesController;
use App\Controller\Admin\OpponentsController;
use App\Controller\Admin\PersonsController;
use App\Controller\Admin\PlacesController;
use App\Controller\Admin\RolesController;
use App\Controller\Admin\SeasonsController as AdminSeasonsController;
use App\Controller\Admin\SiteOptionsController;
use App\Controller\Admin\SitesController;
use App\Controller\Admin\StatBasketGameBoxController;
use App\Controller\Admin\StatBasketGameOpponentController;
use App\Controller\Admin\StatBasketGamePersonController;
use App\Controller\Admin\StatBasketGameTeamController;
use App\Controller\Admin\StatBasketSeasonOpponentController;
use App\Controller\Admin\StatBasketSeasonPersonController;
use App\Controller\Admin\StatBasketSeasonTeamController;
use App\Controller\Admin\TagLookupsController;
use App\Controller\Admin\TagsController;
use App\Controller\Admin\TeamsController;
use App\Controller\Admin\TeamSeasonRostersController;
use App\Controller\Admin\TeamSeasonsController;
use App\Controller\Admin\UsersController as AdminUsersController;
use App\Controller\BlogController;
use App\Controller\Component\AdminDataScopeComponent;
use App\Controller\Component\PublicScopeComponent;
use App\Controller\Component\UserManagerComponent;
use App\Controller\Component\UserProfileComponent;
use App\Controller\GamesController;
use App\Controller\ImagesController;
use App\Controller\InstallController;
use App\Controller\PeopleController;
use App\Controller\SeasonsController as PublicSeasonsController;
use App\Controller\StatsController;
use App\Controller\UsersController;
use App\Middleware\DebugSessionMiddleware;
use App\Model\Table\BlogTagsTable;
use App\Model\Table\GamesTable;
use App\Model\Table\ImagesImageTagsTable;
use App\Model\Table\ImagesTable;
use App\Model\Table\ImageTagsTable;
use App\Model\Table\PersonsTable;
use App\Model\Table\TeamSeasonRostersTable;
use App\Model\Table\TeamsTable;
use App\Model\Table\UsersTable;
use App\Policy\RequestPolicy;
use App\Service\AdminDataScopeService;
use App\Service\BasketballBoxScoreImportService;
use App\Service\BasketballOpponentNameNormalizationService;
use App\Service\BasketballStatsAdminService;
use App\Service\BlogPostsAdminService;
use App\Service\BlogPostService;
use App\Service\CacheEngineService;
use App\Service\DashboardAdminService;
use App\Service\DeployAuditService;
use App\Service\GameEavMetaService;
use App\Service\GameEavUiService;
use App\Service\GameListViewService;
use App\Service\GamesAdminService;
use App\Service\GameSearchService;
use App\Service\GameService;
use App\Service\GameTypeAdminService;
use App\Service\GameUpsertService;
use App\Service\GameViewService;
use App\Service\ImageBrowseService;
use App\Service\ImageDeleteService;
use App\Service\ImageEditService;
use App\Service\ImageProcessor;
use App\Service\ImagesAdminService;
use App\Service\ImageStorageService;
use App\Service\ImageTagService;
use App\Service\ImageTagUiService;
use App\Service\ImageUrlService;
use App\Service\OpponentAdminService;
use App\Service\OpponentService;
use App\Service\PasswordResetService;
use App\Service\PeopleDirectoryService;
use App\Service\PersonAdminService;
use App\Service\PersonService;
use App\Service\PlaceAdminService;
use App\Service\PublicSportScopeService;
use App\Service\RbacPermissionService;
use App\Service\RolesAdminService;
use App\Service\SeasonAdminService;
use App\Service\SeasonViewService;
use App\Service\SiteAdminService;
use App\Service\SiteOptionsService;
use App\Service\SiteService;
use App\Service\SportConfigService;
use App\Service\StatsService;
use App\Service\StatsViewService;
use App\Service\TaggingServiceFactory;
use App\Service\TagLookupsAdminService;
use App\Service\TagModalService;
use App\Service\TeamAdminService;
use App\Service\TeamSeasonAdminService;
use App\Service\TeamSeasonRosterAdminService;
use App\Service\TeamSeasonRosterService;
use App\Service\TeamSeasonService;
use App\Service\TeamService;
use App\Service\TeamSportContextService;
use App\Service\UserAccountService;
use App\Service\UserProfileService;
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
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use CakeDC\Users\Loader\AuthenticationServiceLoader;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
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
            $tableLocator = (new TableLocator())->allowFallbackClass(false);
            /** @var \Cake\Datasource\Locator\LocatorInterface<\Cake\Datasource\RepositoryInterface> $tableLocator */
            FactoryLocator::add('Table', $tableLocator);
        }

        // Image variants configuration (central place)
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150,150], 'format' => 'webp'],
            'medium' => ['maxWidth' => 800, 'format' => 'webp'],
            'webp' => ['format' => 'webp'], // WebP alternate of original
        ]);

        // Public image profiles for stable, semantic URLs backed by transforms.
        // Profiles may optionally use a sourceVariant for custom-cropped inputs.
        Configure::write('Images.profiles', [
            'roster_avatar' => [
                'sourceVariant' => 'thumb',
                'w' => 150,
                'h' => 150,
                'fit' => 'cover',
            ],
            'season_billboard' => [
                'sourceVariant' => 'hero',
            ],
            'blog_featured' => [
                'sourceVariant' => 'hero',
            ],
            'blog_index_card' => [
                'w' => 200,
                'h' => 150,
                'fit' => 'cover',
            ],
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

            // Debug middleware (CLI/tests only): expose initial session Auth
            ->add(new DebugSessionMiddleware())

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
            ->add(new AuthorizationMiddleware($this, [
                'requireAuthorizationCheck' => false,
            ]));

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
        $container->addShared(TableLocator::class, static function (): TableLocator {
            $tableLocator = TableRegistry::getTableLocator();
            assert($tableLocator instanceof TableLocator);

            return $tableLocator;
        });
        $container->addShared(PersonsTable::class, static function (): PersonsTable {
            $personsTable = TableRegistry::getTableLocator()->get('Persons');
            assert($personsTable instanceof PersonsTable);

            return $personsTable;
        });
        $container->addShared(TeamSeasonRostersTable::class, static function (): TeamSeasonRostersTable {
            $rostersTable = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
            assert($rostersTable instanceof TeamSeasonRostersTable);

            return $rostersTable;
        });
        $container->addShared(TeamsTable::class, static function (): TeamsTable {
            $teamsTable = TableRegistry::getTableLocator()->get('Teams');
            assert($teamsTable instanceof TeamsTable);

            return $teamsTable;
        });
        $container->addShared(ImageTagsTable::class, static function (): ImageTagsTable {
            $imageTagsTable = TableRegistry::getTableLocator()->get('ImageTags');
            assert($imageTagsTable instanceof ImageTagsTable);

            return $imageTagsTable;
        });
        $container->addShared(BlogTagsTable::class, static function (): BlogTagsTable {
            $blogTagsTable = TableRegistry::getTableLocator()->get('BlogTags');
            assert($blogTagsTable instanceof BlogTagsTable);

            return $blogTagsTable;
        });
        $container->addShared(GamesTable::class, static function (): GamesTable {
            $gamesTable = TableRegistry::getTableLocator()->get('Games');
            assert($gamesTable instanceof GamesTable);

            return $gamesTable;
        });
        $container->addShared(ImagesTable::class, static function (): ImagesTable {
            $imagesTable = TableRegistry::getTableLocator()->get('Images');
            assert($imagesTable instanceof ImagesTable);

            return $imagesTable;
        });
        $container->addShared(ImagesImageTagsTable::class, static function (): ImagesImageTagsTable {
            $imagesImageTagsTable = TableRegistry::getTableLocator()->get('ImagesImageTags');
            assert($imagesImageTagsTable instanceof ImagesImageTagsTable);

            return $imagesImageTagsTable;
        });
        $container->addShared(UsersTable::class, static function (): UsersTable {
            $usersTable = TableRegistry::getTableLocator()->get('Users');
            assert($usersTable instanceof UsersTable);

            return $usersTable;
        });
        $container->add(PublicSportScopeService::class);
        $container->add(AdminDataScopeService::class);
        $container->add(RbacPermissionService::class);
        $container->add(BasketballOpponentNameNormalizationService::class);
        $container->add(CacheEngineService::class);
        $container->add(DeployAuditService::class);
        $container->add(SportConfigService::class);
        $container->add(BasketballStatsAdminService::class);
        $container->add(BasketballBoxScoreImportService::class);
        $container->add(SeasonAdminService::class);
        $container->add(DashboardAdminService::class)
            ->addArgument(DeployAuditService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(TeamsTable::class)
            ->addArgument(GamesTable::class)
            ->addArgument(ImagesTable::class)
            ->addArgument(CacheEngineService::class);
        $container->add(StatsService::class);
        $container->add(StatsViewService::class)
            ->addArgument(StatsService::class);
        $container->add(BlogPostService::class)
            ->addArgument(TaggingServiceFactory::class);
        $container->add(GameListViewService::class)
            ->addArgument(GameService::class);
        $container->add(GameSearchService::class);
        $container->add(GameService::class);
        $container->add(GameEavUiService::class);
        $container->add(GameEavMetaService::class)
            ->addArgument(GameService::class)
            ->addArgument(GameEavUiService::class);
        $container->add(GamesAdminService::class)
            ->addArgument(GameService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(RbacPermissionService::class)
            ->addArgument(AdminDataScopeService::class)
            ->addArgument(TeamSportContextService::class);
        $container->add(GameViewService::class)
            ->addArgument(GameService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(StatsService::class)
            ->addArgument(GameEavUiService::class);
        $container->add(GameTypeAdminService::class);
        $container->add(GameUpsertService::class)
            ->addArgument(GamesTable::class)
            ->addArgument(GameService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(GameEavUiService::class)
            ->addArgument(AdminDataScopeService::class);
        $container->add(ImageProcessor::class);
        $container->add(ImageBrowseService::class);
        $container->add(ImageUrlService::class)
            ->addArgument(ImagesTable::class);
        $container->add(ImageTagService::class);
        $container->add(ImagesAdminService::class)
            ->addArgument(ImagesTable::class)
            ->addArgument(ImageDeleteService::class)
            ->addArgument(ImageEditService::class)
            ->addArgument(RbacPermissionService::class)
            ->addArgument(TaggingServiceFactory::class)
            ->addArgument(UsersTable::class)
            ->addArgument(TeamService::class)
            ->addArgument(TeamSeasonService::class)
            ->addArgument(GameService::class)
            ->addArgument(SiteService::class)
            ->addArgument(OpponentService::class)
            ->addArgument(TeamSportContextService::class)
            ->addArgument(ImageTagUiService::class);
        $container->add(OpponentService::class);
        $container->add(OpponentAdminService::class);
        $container->add(PersonService::class);
        $container->add(PersonAdminService::class);
        $container->add(PlaceAdminService::class);
        $container->add(SiteService::class);
        $container->add(SiteAdminService::class);
        $container->add(TeamService::class);
        $container->add(TeamAdminService::class);
        $container->add(TeamSeasonService::class);
        $container->add(TeamSeasonAdminService::class);
        $container->add(TeamSeasonRosterAdminService::class);
        $container->add(SeasonViewService::class)
            ->addArgument(TeamSeasonService::class);
        $container->add(TeamSeasonRosterService::class);
        $container->add(ImageTagUiService::class)
            ->addArgument(TeamSeasonRosterService::class);
        $container->add(BlogPostsAdminService::class)
            ->addArgument(BlogPostService::class);
        $container->add(RolesAdminService::class);
        $container->add(SiteOptionsService::class);
        $container->add(TagLookupsAdminService::class);
        $container->add(PeopleDirectoryService::class)
            ->addArgument(PersonsTable::class)
            ->addArgument(TeamSeasonRostersTable::class)
            ->addArgument(PersonService::class)
            ->addArgument(StatsService::class)
            ->addArgument(TeamSportContextService::class)
            ->addArgument(PublicSportScopeService::class);
        $container->add(TeamSportContextService::class);
        $container->add(TaggingServiceFactory::class)
            ->addArgument(TableLocator::class)
            ->addArgument(PersonService::class)
            ->addArgument(TeamSeasonService::class)
            ->addArgument(TeamSeasonRosterService::class)
            ->addArgument(TeamSportContextService::class)
            ->addArgument(GameService::class);
        $container->add(TagModalService::class)
            ->addArgument(ImagesAdminService::class)
            ->addArgument(BlogPostsAdminService::class)
            ->addArgument(TeamService::class)
            ->addArgument(TeamSeasonService::class)
            ->addArgument(GameService::class)
            ->addArgument(SiteService::class)
            ->addArgument(OpponentService::class)
            ->addArgument(TeamSportContextService::class)
            ->addArgument(ImageTagsTable::class)
            ->addArgument(BlogTagsTable::class)
            ->addArgument(TaggingServiceFactory::class);
        $container->add(PublicScopeComponent::class)
            ->addArgument(ComponentRegistry::class)
            ->addArgument(PublicSportScopeService::class)
            ->addArgument(StatsService::class)
            ->addArgument([]);
        $container->add(AdminDataScopeComponent::class)
            ->addArgument(ComponentRegistry::class)
            ->addArgument(AdminDataScopeService::class)
            ->addArgument([]);
        $container->add(BlogController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BlogPostService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(ImagesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(ImageUrlService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(GamesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(GameService::class)
            ->addArgument(GameViewService::class)
            ->addArgument(StatsService::class)
            ->addArgument(ImageTagService::class)
            ->addArgument(BlogPostService::class)
            ->addArgument(GameSearchService::class)
            ->addArgument(GameListViewService::class)
            ->addArgument(OpponentService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(PublicSeasonsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TeamSeasonService::class)
            ->addArgument(SeasonViewService::class)
            ->addArgument(PublicSportScopeService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(InstallController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(DeployAuditService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(PeopleController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BlogPostService::class)
            ->addArgument(ImageProcessor::class)
            ->addArgument(PeopleDirectoryService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(TagsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TagModalService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(AdminGamesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(GamesAdminService::class)
            ->addArgument(GameService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(StatsService::class)
            ->addArgument(GameEavMetaService::class)
            ->addArgument(GameViewService::class)
            ->addArgument(GameUpsertService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(AdminSeasonsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(SeasonAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(BasketballBoxScoreImportController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballBoxScoreImportService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(TeamSeasonRostersController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TeamSeasonRosterAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(OpponentsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(OpponentAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(PersonsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(PersonAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(GameTypesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(GameTypeAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(RolesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(RolesAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(BlogPostsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BlogPostsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(PlacesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(PlaceAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(TeamsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TeamAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(SitesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(SiteAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(TagLookupsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TagLookupsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(TeamSeasonsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(TeamSeasonAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(SiteOptionsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(SiteOptionsService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketGameBoxController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketGameOpponentController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketGamePersonController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketGameTeamController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketSeasonOpponentController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketSeasonPersonController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatBasketSeasonTeamController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(BasketballStatsAdminService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(DashboardController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(DashboardAdminService::class)
            ->addArgument(BasketballOpponentNameNormalizationService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(AdminUsersController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(SiteOptionsService::class)
            ->addArgument(RolesAdminService::class)
            ->addArgument(RbacPermissionService::class)
            ->addArgument(ImageBrowseService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(AdminImagesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(ImagesAdminService::class)
            ->addArgument(ImageBrowseService::class)
            ->addArgument(ImageStorageService::class)
            ->addArgument(ImageUrlService::class)
            ->addArgument(PersonService::class)
            ->addArgument(TeamSeasonRosterService::class)
            ->addArgument(TaggingServiceFactory::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(StatsController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(StatsService::class)
            ->addArgument(StatsViewService::class)
            ->addArgument(SportConfigService::class)
            ->addArgument(PublicSportScopeService::class)
            ->addArgument(TeamSportContextService::class)
            ->addArgument(TeamSeasonService::class)
            ->addArgument(ComponentRegistry::class);
        $container->add(ImageStorageService::class)
            ->addArgument(ImageProcessor::class)
            ->addArgument(TaggingServiceFactory::class)
            ->addArgument(ImagesTable::class);
        $container->add(ImageDeleteService::class)
            ->addArgument(ImageStorageService::class)
            ->addArgument(TaggingServiceFactory::class)
            ->addArgument(ImagesTable::class)
            ->addArgument(ImagesImageTagsTable::class);
        $container->add(ImageEditService::class)
            ->addArgument(ImageProcessor::class)
            ->addArgument(ImageStorageService::class);
        $container->add(PasswordResetService::class)
            ->addArgument(UsersTable::class);
        $container->add(UserAccountService::class)
            ->addArgument(UsersTable::class);
        $container->add(UserProfileService::class)
            ->addArgument(UsersTable::class)
            ->addArgument(ImageStorageService::class);
        $container->add(UserProfileComponent::class)
            ->addArgument(ComponentRegistry::class)
            ->addArgument(UserProfileService::class)
            ->addArgument([]);
        $container->add(UserManagerComponent::class)
            ->addArgument(ComponentRegistry::class)
            ->addArgument(UserAccountService::class)
            ->addArgument(PasswordResetService::class)
            ->addArgument([]);
        $container->add(UsersController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(UserAccountService::class)
            ->addArgument(PasswordResetService::class)
            ->addArgument(ComponentRegistry::class);
    }

    /**
     * Returns a configured authentication service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        // CakePHP Authentication 3.3+ deprecated configuring identifiers separately and/or
        // calling loadIdentifier(). CakeDC/Users still loads identifiers from Auth.Identifiers,
        // which triggers deprecations when AuthenticationService builds the AuthenticatorCollection.
        // Keep CakeDC/Users in place, but move the Password identifier config onto the Form authenticator.
        $serviceLoader = Configure::read('Auth.Authentication.serviceLoader');
        if ($serviceLoader === AuthenticationServiceLoader::class) {
            Configure::write('Auth.Identifiers', []);
            Configure::write('Auth.PasswordRehash.identifiers', []);

            $authenticators = (array)Configure::read('Auth.Authenticators');

            $sessionConfig = $authenticators['Session'] ?? null;
            if (!is_array($sessionConfig)) {
                $sessionConfig = [
                    'className' => 'Authentication.Session',
                    'skipTwoFactorVerify' => true,
                    'sessionKey' => 'Auth',
                ];
            }

            $formConfig = $authenticators['Form'] ?? null;
            if (is_array($formConfig)) {
                $formConfig['identifier'] = [
                    'Password' => [
                        'className' => 'Authentication.Password',
                        'fields' => [
                            'username' => ['username', 'email'],
                            'password' => 'password',
                        ],
                        'resolver' => [
                            'className' => 'Authentication.Orm',
                            'finder' => 'active',
                        ],
                    ],
                ];
                Configure::write('Auth.Authenticators', [
                    'Session' => $sessionConfig,
                    'Form' => $formConfig,
                ]);
            }
        }

        if (is_string($serviceLoader) && class_exists($serviceLoader)) {
            /** @var object&callable $loader */
            $loader = new $serviceLoader();
            $service = $loader($request);

            // Keep app behavior consistent (admin redirects use ?redirect=...)
            if (method_exists($service, 'setConfig')) {
                $service->setConfig([
                    'unauthenticatedRedirect' => '/users/login',
                    'queryParam' => 'redirect',
                ]);
            }

            return $service;
        }

        // Fallback (non-CakeDC auth service)
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => '/users/login',
            'queryParam' => 'redirect',
        ]);
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
        $mapResolver->map(ServerRequest::class, RequestPolicy::class);

        // Create resolver collection with both map and ORM resolvers
        $resolvers = new ResolverCollection([
            $mapResolver,
            new OrmResolver(),
        ]);

        $service = new AuthorizationService($resolvers);

        return $service;
    }
}
