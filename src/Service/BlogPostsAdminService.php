<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\BlogPost;
use App\Model\Table\BlogPostsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query\SelectQuery as OrmSelectQuery;
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

    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Service\BlogPostService|null $blogPostService
     * @param \App\Model\Table\BlogPostsTable|null $blogPostsTable
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService
     */
    public function __construct(
        ?BlogPostService $blogPostService = null,
        ?BlogPostsTable $blogPostsTable = null,
        ?RbacPermissionService $rbacPermissionService = null,
    ) {
        $this->blogPostService = $blogPostService ?? new BlogPostService();

        /** @var \App\Model\Table\BlogPostsTable $table */
        $table = $blogPostsTable ?? TableRegistry::getTableLocator()->get('BlogPosts');
        $this->blogPostsTable = $table;
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
    }

    /**
     * Fetch admin list data.
     *
     * @param mixed $identity Current request identity.
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    public function getIndexPosts(mixed $identity = null): array
    {
        $query = $this->blogPostsTable->find()
            ->contain(['BlogTags'])
            ->orderByDesc('BlogPosts.created');
        $query = $this->applyScope($identity, 'index', $query, 'BlogPosts');

        /** @var array<int,\App\Model\Entity\BlogPost> $posts */
        $posts = $query->all()->toList();

        return $posts;
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
     * @param mixed $identity Current request identity.
     */
    public function getEditEntity(int $id, mixed $identity = null): BlogPost
    {
        $query = $this->blogPostsTable->find()->contain(['BlogTags']);
        $query = $this->applyScope($identity, 'update', $query, 'BlogPosts');

        /** @var \App\Model\Entity\BlogPost|null $post */
        $post = $query->where(['BlogPosts.id' => $id])->first();
        if (!$post instanceof BlogPost) {
            throw new RecordNotFoundException('Blog post not found or not accessible.');
        }

        return $post;
    }

    /**
     * Process add submission.
     *
     * @param array<string,mixed> $data
     * @param mixed $identity Current request identity.
     * @return array{success:bool,post:\App\Model\Entity\BlogPost,createdId?:int}
     */
    public function add(array $data, mixed $identity = null): array
    {
        if (!$this->rbacPermissionService->can($identity, 'BlogPosts', 'create')) {
            $post = $this->blogPostsTable->newEntity($data);

            return [
                'success' => false,
                'post' => $post,
            ];
        }

        $data = $this->sanitizeSubmittedData($data, $identity, true);
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
     * @param mixed $identity Current request identity.
     * @return array{success:bool,post:\App\Model\Entity\BlogPost}
     */
    public function edit(int $id, array $data, mixed $identity = null): array
    {
        $this->getEditEntity($id, $identity);
        $data = $this->sanitizeSubmittedData($data, $identity, false);

        $saved = $this->blogPostService->updatePost($id, $data);
        if ($saved !== false) {
            return [
                'success' => true,
                'post' => $saved,
            ];
        }

        $post = $this->getEditEntity($id, $identity);
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
     * @param mixed $identity Current request identity.
     * @return bool
     */
    public function delete(int $id, mixed $identity = null): bool
    {
        $query = $this->blogPostsTable->find();
        $query = $this->applyScope($identity, 'delete', $query, 'BlogPosts');
        /** @var \App\Model\Entity\BlogPost|null $post */
        $post = $query->where(['BlogPosts.id' => $id])->first();
        if (!$post instanceof BlogPost) {
            throw new RecordNotFoundException('Blog post not found or not accessible.');
        }

        return $this->blogPostService->deletePost($id);
    }

    /**
     * Build all shared view variables required by add/edit templates.
     *
     * @param \App\Model\Entity\BlogPost $post
     * @param mixed $identity Current request identity.
     * @return array<string,mixed>
     */
    public function buildFormViewData(BlogPost $post, mixed $identity = null): array
    {
        $teams = (new TeamService())->getTeamsForSelect();
        $teamSeasons = (new TeamSeasonService())->getTeamSeasonsForSelect();
        $games = (new GameService())->getRecentGamesForSelect(200);
        $sites = (new SiteService())->getSitesForSelect();
        $opponents = (new OpponentService())->getOpponentsForSelect();
        $sports = [];
        foreach ((new TeamSportContextService())->getLegacySportOptions() as $sportId => $label) {
            $sports[] = [
                'id' => (int)$sportId,
                'label' => (string)$label,
            ];
        }

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

        // Prepare users list for owner selection in admin form
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $users = $usersTable->find('list', [
            'keyField' => 'id',
            'valueField' => 'username',
        ])->orderAsc('username')->toArray();

        $canPinPosts = $this->rbacPermissionService->allowsCustomRule(
            $identity,
            'BlogPosts',
            RbacPermissionService::BLOG_RULE_CAN_PIN,
        );
        $canManagePinSettings = $this->rbacPermissionService->allowsCustomRule(
            $identity,
            'BlogPosts',
            RbacPermissionService::BLOG_RULE_CAN_MANAGE_PIN_SETTINGS,
        );
        $canManagePostOwner = $this->rbacPermissionService->can($identity, 'BlogPosts', 'delete');
        $postOwnerLabel =
            $post->user_id && isset($users[(int)$post->user_id])
                ? $users[(int)$post->user_id]
                : 'Unassigned';

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
            'users',
            'canPinPosts',
            'canManagePinSettings',
            'canManagePostOwner',
            'postOwnerLabel',
        );
    }

    /**
     * Normalize restricted fields based on current identity permissions.
     *
     * @param array<string, mixed> $data Submitted payload.
     * @param mixed $identity Current request identity.
     * @param bool $isCreate Whether this is a create operation.
     * @return array<string, mixed>
     */
    private function sanitizeSubmittedData(
        array $data,
        mixed $identity,
        bool $isCreate,
    ): array {
        $sanitized = $data;

        $canPinPosts = $this->rbacPermissionService->allowsCustomRule(
            $identity,
            'BlogPosts',
            RbacPermissionService::BLOG_RULE_CAN_PIN,
        );
        $canManagePinSettings = $this->rbacPermissionService->allowsCustomRule(
            $identity,
            'BlogPosts',
            RbacPermissionService::BLOG_RULE_CAN_MANAGE_PIN_SETTINGS,
        );

        if (!$canPinPosts) {
            unset($sanitized['is_pinned'], $sanitized['pinned_rank'], $sanitized['pinned_until']);
        } elseif (!$canManagePinSettings) {
            unset($sanitized['pinned_rank'], $sanitized['pinned_until']);
        }

        $actingUserId = $this->extractIdentityId($identity);
        $canManagePostOwner = $this->rbacPermissionService->can($identity, 'BlogPosts', 'delete');
        if ($canManagePostOwner) {
            if ($isCreate && empty($sanitized['user_id']) && $actingUserId !== null) {
                $sanitized['user_id'] = $actingUserId;
            }

            return $sanitized;
        }

        if ($isCreate && $actingUserId !== null) {
            $sanitized['user_id'] = $actingUserId;
        } else {
            unset($sanitized['user_id']);
        }

        return $sanitized;
    }

    /**
     * Extract the numeric user id from known identity shapes.
     *
     * @param mixed $identity Current request identity.
     * @return int|null
     */
    private function extractIdentityId(mixed $identity): ?int
    {
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $identifier = $identity->getIdentifier();
            if (is_numeric($identifier)) {
                return (int)$identifier;
            }
        }
        if (is_array($identity) && !empty($identity['id'])) {
            return (int)$identity['id'];
        }

        return null;
    }

    /**
     * Apply RBAC scope to a BlogPosts query for the current identity.
     *
     * @param mixed $identity Current request identity.
     * @param string $action Requested ability/action.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @param string $modelName Canonical RBAC model name.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function applyScope(
        mixed $identity,
        string $action,
        OrmSelectQuery $query,
        string $modelName,
    ): OrmSelectQuery {
        if (is_object($identity) && method_exists($identity, 'applyScope')) {
            return $identity->applyScope($action, $query);
        }

        return $this->rbacPermissionService->scopeQuery($identity, $modelName, $query, $action, 'user_id');
    }
}
