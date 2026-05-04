<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\BlogPost;
use App\Model\Table\BlogPostsTable;
use Cake\ORM\TableRegistry;

/**
 * BlogPostsAdminService
 *
 * Owns the complete admin slice for BlogPost CRUD and edit-form hydration.
 *
 * Responsibilities:
 * - Admin listing data retrieval.
 * - Add/edit orchestration using BlogPostService.
 * - Delete orchestration.
 * - Shared edit-form data assembly (lookup lists + current tag selections).
 *
 * HTTP concerns (method checks, flash, redirects, rendering) remain in
 * Admin/BlogPostsController.
 */
class BlogPostsAdminService
{
    /**
     * @var \App\Service\BlogPostService
     */
    private BlogPostService $blogPostService;

    /**
     * @var \App\Model\Table\BlogPostsTable
     */
    private BlogPostsTable $blogPostsTable;

    /**
     * @param \App\Service\BlogPostService|null $blogPostService
     * @param \App\Model\Table\BlogPostsTable|null $blogPostsTable
     */
    public function __construct(
        ?BlogPostService $blogPostService = null,
        ?BlogPostsTable $blogPostsTable = null,
    ) {
        $this->blogPostService = $blogPostService ?? new BlogPostService();

        /** @var \App\Model\Table\BlogPostsTable $table */
        $table = $blogPostsTable ?? TableRegistry::getTableLocator()->get('BlogPosts');
        $this->blogPostsTable = $table;
    }

    /**
     * Fetch admin list data.
     *
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    public function getIndexPosts(): array
    {
        return $this->blogPostService->getAllPosts();
    }

    /**
     * Create a new empty BlogPost entity for add form rendering.
     */
    public function newEntity(): BlogPost
    {
        /** @var \App\Model\Entity\BlogPost $post */
        $post = $this->blogPostsTable->newEmptyEntity();

        return $post;
    }

    /**
     * Load a blog post for edit, including tags.
     *
     * @param int $id
     */
    public function getEditEntity(int $id): BlogPost
    {
        /** @var \App\Model\Entity\BlogPost $post */
        $post = $this->blogPostsTable->get($id, contain: ['BlogTags']);

        return $post;
    }

    /**
     * Process add submission.
     *
     * @param array<string,mixed> $data
     * @return array{success:bool,post:\App\Model\Entity\BlogPost,createdId?:int}
     */
    public function add(array $data): array
    {
        $created = $this->blogPostService->createPost($data);
        if ($created !== false) {
            return [
                'success' => true,
                'post' => $created,
                'createdId' => (int)$created->id,
            ];
        }

        $post = $this->newEntity();
        $post = $this->blogPostsTable->patchEntity($post, $data);

        return [
            'success' => false,
            'post' => $post,
        ];
    }

    /**
     * Process edit submission.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @return array{success:bool,post:\App\Model\Entity\BlogPost}
     */
    public function edit(int $id, array $data): array
    {
        $saved = $this->blogPostService->updatePost($id, $data);
        if ($saved !== false) {
            return [
                'success' => true,
                'post' => $saved,
            ];
        }

        $post = $this->getEditEntity($id);
        $post = $this->blogPostsTable->patchEntity($post, $data);

        return [
            'success' => false,
            'post' => $post,
        ];
    }

    /**
     * Delete a post by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->blogPostService->deletePost($id);
    }

    /**
     * Build all shared view variables required by add/edit templates.
     *
     * @param \App\Model\Entity\BlogPost $post
     * @return array<string,mixed>
     */
    public function buildFormViewData(BlogPost $post): array
    {
        $teams = (new TeamService())->getTeamsForSelect();
        $teamSeasons = (new TeamSeasonService())->getTeamSeasonsForSelect();
        $games = (new GameService())->getRecentGamesForSelect(200);
        $sites = (new SiteService())->getSitesForSelect();
        $opponents = (new OpponentService())->getOpponentsForSelect();
        $sports = (new SportService())->getSportsForSelect();

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

        foreach ($currentTags as $tag) {
            $slug = (string)($tag->slug ?? '');
            if (str_starts_with($slug, 'person-')) {
                $selectedPersonId = (int)substr($slug, strlen('person-'));
                $selectedPersonName = (new PersonService())->getDisplayLabel($selectedPersonId);
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
                    $display = (new TeamSeasonRosterService())->getRosterDisplayData($rid);
                    $tag->name = $display['team_season_label'] ?? $tag->name;
                }
                $formattedTags[] = $tag;
            } else {
                $freeformTags[] = $tag;
            }
        }

        $currentTags = array_merge($formattedTags, $freeformTags);
        $tagString = implode(', ', array_map(fn($t) => (string)$t->name, $freeformTags));

        return compact(
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
            'selectedSportId',
        );
    }
}
