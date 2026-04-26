<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BlogPostService;
use App\Service\GameService;
use Cake\Http\Response;

/**
 * Admin Blog Posts Controller
 *
 * Provides CRUD operations for managing blog posts in the admin interface. The index action lists all posts, while the add and edit actions allow for creating and updating posts, respectively. The delete action handles post deletion. The controller uses BlogPostService to abstract the business logic of managing blog posts, keeping the controller focused on request handling and response formatting. The setFormData method prepares shared data for the add and edit forms, including related entities for tagging and selection fields.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage blog posts. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action should use POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - BlogPostService: Provides methods for creating, updating, retrieving, and deleting blog posts, abstracting away the details of these operations from the controller.
 * - GameService: Used to retrieve recent games for selection in the blog post form.
 * - Other services (TeamService, TeamSeasonService, SiteService, OpponentService, SportService) are used to retrieve related data for form selections and tagging.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * @property \App\Service\BlogPostService $blogPostService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\BlogPostsTable $BlogPosts
 * @property \App\Service\TeamService $teamService
 * @property \App\Service\TeamSeasonService $teamSeasonService
 * @property \App\Service\SiteService $siteService
 * @property \App\Service\OpponentService $opponentService
 * @property \App\Service\SportService $sportService
 */
class BlogPostsController extends AppController
{
    private BlogPostService $blogPostService;

    /**
     * Controller initialize: setup BlogPostService.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->blogPostService = new BlogPostService();
    }

    /**
     * Admin index listing.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $posts = $this->blogPostService->getAllPosts();
        $this->set(compact('posts'));
    }

    /**
     * Add new post.
     */
    public function add(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $postsTable = $this->fetchTable('BlogPosts');
        $post = $postsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = (array)$this->request->getData();
            $created = $this->blogPostService->createPost($data);
            if ($created) {
                $this->Flash->success('The blog post has been saved.');

                return $this->redirect(['action' => 'edit', $created->id]);
            }
            $this->Flash->error('The blog post could not be saved. Please, try again.');
            $post = $postsTable->patchEntity($post, $data);
        }

        $this->setFormData($post);
        $this->viewBuilder()->setTemplate('edit');

        return null;
    }

    /**
     * Edit post.
     */
    public function edit(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post', 'put', 'patch']);
        $postsTable = $this->fetchTable('BlogPosts');
        $post = $postsTable->get($id, contain: ['BlogTags', 'HeroImages']);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = (array)$this->request->getData();
            $saved = $this->blogPostService->updatePost($id, $data);
            if ($saved) {
                $this->Flash->success('The blog post has been saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }
            $this->Flash->error('The blog post could not be saved. Please, try again.');
            $post = $postsTable->patchEntity($post, $data);
        }

        $this->setFormData($post);

        return null;
    }

    /**
     * Delete post.
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->blogPostService->deletePost($id)) {
            $this->Flash->success('The blog post has been deleted.');
        } else {
            $this->Flash->error('The blog post could not be deleted. Please, try again.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Prepare shared form data for add/edit.
     *
     * @param \App\Model\Entity\BlogPost $post The blog post being edited
     * @return void
     */
    private function setFormData(\App\Model\Entity\BlogPost $post): void
    {
        $teams = (new \App\Service\TeamService())->getTeamsForSelect();
        $teamSeasons = (new \App\Service\TeamSeasonService())->getTeamSeasonsForSelect();

        $games = (new GameService())->getRecentGamesForSelect(200);

        $siteService = new \App\Service\SiteService();
        $sites = $siteService->getSitesForSelect();

        $opponentService = new \App\Service\OpponentService();
        $opponents = $opponentService->getOpponentsForSelect();

        $sportService = new \App\Service\SportService();
        $sports = $sportService->getSportsForSelect();

        $currentTags = $post->blog_tags ?? [];
        $formattedTags = [];
        $freeformTags = [];
        $selectedPersonId = null;
        $selectedPersonName = null;
        $selectedRosterId = null;
        $selectedTeamSeasonId = null;
        $selectedGameId = null;
        $selectedSiteId = null;
        $selectedOpponentId = null;
        $selectedTeamId = null;
        $selectedSportId = null;

        foreach ($currentTags as $t) {
            $slug = (string)($t->slug ?? '');
            if (str_starts_with($slug, 'person-')) {
                $selectedPersonId = (int)substr($slug, strlen('person-'));
                $selectedPersonName = (new \App\Service\PersonService())->getDisplayLabel($selectedPersonId);
            }
            if (str_starts_with($slug, 'teamseason-')) {
                $selectedTeamSeasonId = (int)substr($slug, strlen('teamseason-'));
            }
            if (str_starts_with($slug, 'game-')) {
                $selectedGameId = (int)substr($slug, strlen('game-'));
            }
            if (str_starts_with($slug, 'site-')) {
                $selectedSiteId = (int)substr($slug, strlen('site-'));
            }
            if (str_starts_with($slug, 'opponent-')) {
                $selectedOpponentId = (int)substr($slug, strlen('opponent-'));
            }
            if (str_starts_with($slug, 'team-')) {
                $selectedTeamId = (int)substr($slug, strlen('team-'));
            }
            if (str_starts_with($slug, 'sport-')) {
                $selectedSportId = (int)substr($slug, strlen('sport-'));
            }
            if (str_starts_with($slug, 'team_season_roster-')) {
                $selectedRosterId = (int)substr($slug, strlen('team_season_roster-'));
            }

            if (preg_match('/-[0-9]+$/', $slug)) {
                if (str_starts_with($slug, 'team_season_roster-')) {
                    $rid = (int)substr($slug, strlen('team_season_roster-'));
                    $display = (new \App\Service\TeamSeasonRosterService())->getRosterDisplayData($rid);
                    $t->name = $display['team_season_label'] ?? $t->name;
                }
                $formattedTags[] = $t;
            } else {
                $freeformTags[] = $t;
            }
        }

        $currentTags = array_merge($formattedTags, $freeformTags);
        $tagString = implode(', ', array_map(fn($t) => $t->name, $freeformTags));

        $this->set(compact(
            'post',
            'teams',
            'teamSeasons',
            'games',
            'sites',
            'opponents',
            'sports',
            'currentTags',
            'tagString',
            'selectedPersonId',
            'selectedPersonName',
            'selectedRosterId',
            'selectedTeamSeasonId',
            'selectedGameId',
            'selectedSiteId',
            'selectedOpponentId',
            'selectedTeamId',
            'selectedSportId'
        ));
    }
}
