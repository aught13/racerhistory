<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PersonService;
use App\Service\ImageProcessor;
use App\Service\BlogPostService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Public People Controller
 *
 * Displays persons (players/coaches/staff) with related images and blog posts.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PeopleController extends AppController
{
    private PersonService $personService;
    private ImageProcessor $imageProcessor;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->personService = new PersonService();
        $this->imageProcessor = new ImageProcessor();
    }

    /**
     * Skip authorization for public actions.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * List all people.
     */
    public function index(): void
    {
        $table = $this->fetchTable('Persons');
        $people = $table->find()
            ->orderByAsc('Persons.last')
            ->orderByAsc('Persons.first')
            ->all()
            ->toArray();

        $this->set(compact('people'));
    }

    /**
     * View a single person with related data.
     *
     * @param int $id Person ID
     */
    public function view(int $id): void
    {
        $person = $this->personService->getPersonById($id);
        if (!$person) {
            throw new NotFoundException('Person not found');
        }

        // Get related images via tagging
        $images = $this->imageProcessor->getImagesForPerson($id, 20);

        // Get related blog posts via tagging
        $blogPosts = $this->getBlogPostsByTag("person-{$id}");

        // Get roster entries (team seasons this person was on)
        $rosterEntries = $this->getRosterEntriesForPerson($id);

        // Get game stats if available
        $gameStats = $this->getGameStatsForPerson($id);

        $this->set(compact('person', 'images', 'blogPosts', 'rosterEntries', 'gameStats'));
    }

    /**
     * Get blog posts by tag slug.
     *
     * @param string $tagSlug Tag slug
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    private function getBlogPostsByTag(string $tagSlug): array
    {
        $table = $this->fetchTable('BlogPosts');
        $posts = $table->find()
            ->contain(['BlogTags', 'HeroImages'])
            ->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            })
            ->where(['BlogPosts.is_published' => true])
            ->orderByDesc('BlogPosts.published_at')
            ->limit(10)
            ->all()
            ->toArray();

        return $posts;
    }

    /**
     * Get roster entries for a person.
     *
     * @param int $personId Person ID
     * @return array<int,\App\Model\Entity\TeamSeasonRosters>
     */
    private function getRosterEntriesForPerson(int $personId): array
    {
        $table = $this->fetchTable('TeamSeasonRosters');
        $entries = $table->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        return $entries;
    }

    /**
     * Get game stats for a person.
     *
     * @param int $personId Person ID
     * @return array<int,\App\Model\Entity\StatBasketGamePerson>
     */
    private function getGameStatsForPerson(int $personId): array
    {
        try {
            $table = $this->fetchTable('StatBasketGamePersons');
            $stats = $table->find()
                ->contain(['Games' => ['Opponents', 'TeamSeasons' => ['Seasons']]])
                ->where(['StatBasketGamePersons.person_id' => $personId])
                ->orderByDesc('Games.game_date')
                ->limit(20)
                ->all()
                ->toArray();

            return $stats;
        } catch (\Exception $e) {
            // Table might not exist if basketball stats not enabled
            return [];
        }
    }
}
