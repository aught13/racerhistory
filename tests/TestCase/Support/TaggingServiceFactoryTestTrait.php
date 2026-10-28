<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

use App\Service\GameService;
use App\Service\PersonService;
use App\Service\TaggingService;
use App\Service\TaggingServiceFactory;
use App\Service\TeamSeasonRosterService;
use App\Service\TeamSeasonService;
use App\Service\TeamSportContextService;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;

trait TaggingServiceFactoryTestTrait
{
    /**
     * Create a tagging service factory using the test table locator.
     *
     * @return \App\Service\TaggingServiceFactory
     */
    protected function taggingServiceFactory(): TaggingServiceFactory
    {
        $tableLocator = TableRegistry::getTableLocator();
        assert($tableLocator instanceof TableLocator);

        $personService = new PersonService();
        $teamSeasonService = new TeamSeasonService();

        return new TaggingServiceFactory(
            $tableLocator,
            $personService,
            $teamSeasonService,
            new TeamSeasonRosterService($personService, $teamSeasonService),
            new TeamSportContextService(),
            new GameService(),
        );
    }

    /**
     * Create a tagging service configured for images.
     *
     * @return \App\Service\TaggingService
     */
    protected function imageTaggingService(): TaggingService
    {
        return $this->taggingServiceFactory()->forImages();
    }

    /**
     * Create a tagging service configured for blog posts.
     *
     * @return \App\Service\TaggingService
     */
    protected function blogPostTaggingService(): TaggingService
    {
        return $this->taggingServiceFactory()->forBlogPosts();
    }
}
