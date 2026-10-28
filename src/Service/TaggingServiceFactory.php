<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\BlogPostsTable;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;

class TaggingServiceFactory
{
    /**
     * Create the tagging service factory.
     *
     * @param \Cake\ORM\Locator\TableLocator $tableLocator The application table locator.
     * @param \App\Service\PersonService $personService The person service.
     * @param \App\Service\TeamSeasonService $teamSeasonService The team season service.
     * @param \App\Service\TeamSeasonRosterService $rosterService The team season roster service.
     * @param \App\Service\TeamSportContextService $teamSportContextService The team sport context service.
     * @param \App\Service\GameService $gameService The game service.
     */
    public function __construct(
        private TableLocator $tableLocator,
        private PersonService $personService,
        private TeamSeasonService $teamSeasonService,
        private TeamSeasonRosterService $rosterService,
        private TeamSportContextService $teamSportContextService,
        private GameService $gameService,
    ) {
    }

    /**
     * Create the image tagging service.
     *
     * @return \App\Service\TaggingService
     */
    public function forImages(): TaggingService
    {
        return $this->create(
            $this->tableLocator->get('Images'),
            $this->tableLocator->get('ImageTags'),
            'ImageTags',
            ['pruneOrphans' => true, 'allowContextTags' => true],
        );
    }

    /**
     * Create the blog post tagging service.
     *
     * @return \App\Service\TaggingService
     */
    public function forBlogPosts(): TaggingService
    {
        return $this->create(
            $this->tableLocator->get('BlogPosts'),
            $this->tableLocator->get('BlogTags'),
            'BlogTags',
            [],
        );
    }

    /**
     * @param \Cake\ORM\Table $subjectTable The subject table.
     * @param \Cake\ORM\Table $tagsTable The tag association table.
     * @param string $associationName The subject-to-tags association.
     * @param array{pruneOrphans?:bool,allowContextTags?:bool} $options
     */
    private function create(
        Table $subjectTable,
        Table $tagsTable,
        string $associationName,
        array $options,
    ): TaggingService {
        $blogPostsTable = $this->tableLocator->get('BlogPosts');
        assert($blogPostsTable instanceof BlogPostsTable);

        return new TaggingService(
            $subjectTable,
            $tagsTable,
            $associationName,
            $options,
            $this->labelTables(),
            $blogPostsTable,
            $this->personService,
            $this->teamSeasonService,
            $this->rosterService,
            $this->teamSportContextService,
            $this->gameService,
        );
    }

    /**
     * @return array<string,\Cake\ORM\Table>
     */
    private function labelTables(): array
    {
        return [
            'Games' => $this->tableLocator->get('Games'),
            'Sites' => $this->tableLocator->get('Sites'),
            'Opponents' => $this->tableLocator->get('Opponents'),
            'Teams' => $this->tableLocator->get('Teams'),
        ];
    }
}
