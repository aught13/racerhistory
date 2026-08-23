<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\BlogPost;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * BlogPostService
 *
 * Provides CRUD operations for blog posts and delegates tag handling to TaggingService.
 */
class BlogPostService
{
    private TaggingService $tagging;

    /**
     * Create the service with an optional tagging dependency.
     *
     * @param \App\Service\TaggingService|null $tagging Tagging helper, defaults to BlogPosts context.
     */
    public function __construct(?TaggingService $tagging = null)
    {
        $this->tagging = $tagging ?? TaggingService::forBlogPosts();
    }

    /**
     * Fetch a blog post by id.
     *
     * @param int $id
     */
    public function getPostById(int $id): ?BlogPost
    {
        $table = $this->posts();
        /** @var \App\Model\Entity\BlogPost|null $post */
        $post = $table->find()->contain(['BlogTags'])->where(['BlogPosts.id' => $id])->first();

        return $post;
    }

    /**
     * Fetch a published blog post by slug.
     *
     * @param string $slug
     */
    public function getPublishedBySlug(string $slug): ?BlogPost
    {
        $table = $this->posts();
        /** @var \App\Model\Entity\BlogPost|null $post */
        $post = $table->find()
            ->contain(['BlogTags', 'Users', 'HeroImages'])
            ->where([
                'BlogPosts.slug' => $slug,
                'BlogPosts.is_published' => true,
            ])
            ->first();

        return $post;
    }

    /**
     * Get posts for public listing (published only).
     *
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    public function getPublishedPosts(): array
    {
        $rows = $this->getPublishedPostsQuery()
            ->all()
            ->toArray();

        return $this->normalizePosts($rows);
    }

    /**
     * Get published posts by a tag slug.
     *
     * @param string $tagSlug
     * @param int $limit
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    public function getPublishedByTag(string $tagSlug, int $limit = 20): array
    {
        $table = $this->posts();

        $rows = $table->find()
            ->contain(['BlogTags'])
            ->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            })
            ->where(['BlogPosts.is_published' => true])
            ->orderByDesc('BlogPosts.published_at')
            ->limit($limit)
            ->all()
            ->toArray();

        return $this->normalizePosts($rows);
    }

    /**
     * Get published posts with pagination metadata.
     *
     * @param int $limit
     * @param int $offset
     * @param bool $ignorePinned
     * @param string|null $tagSlug Optional tag slug filter
     * @return array{posts:array<int,\App\Model\Entity\BlogPost>,total:int}
     */
    public function getPublishedPostsPage(
        int $limit,
        int $offset = 0,
        bool $ignorePinned = false,
        ?string $tagSlug = null,
    ): array {
        $query = $this->getPublishedPostsQuery($ignorePinned, $tagSlug);
        $total = (clone $query)->count();
        $posts = $this->normalizePosts($query->limit($limit)->offset($offset)->all()->toArray());

        return ['posts' => $posts, 'total' => $total];
    }

    /**
     * Build the published posts query with pinned ordering.
     *
     * @param bool $ignorePinned Whether to skip custom pin sorting
     * @param string|null $tagSlug Optional tag slug filter
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function getPublishedPostsQuery(bool $ignorePinned = false, ?string $tagSlug = null): SelectQuery
    {
        $table = $this->posts();
        $query = $table->find()
            ->contain(['BlogTags', 'Users', 'HeroImages'])
            ->where(['BlogPosts.is_published' => true]);

        if ($tagSlug) {
            $query->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            });
        }

        $query->select($table);

        $schema = $table->getSchema();
        if (!$ignorePinned && $schema->hasColumn('is_pinned') && !$tagSlug) {
            return $query
                ->orderByDesc('BlogPosts.is_pinned')
                ->orderByDesc('BlogPosts.pinned_rank')
                ->orderByDesc('BlogPosts.published_at');
        }

        return $query->orderByDesc('BlogPosts.published_at');
    }

    /**
     * Get popular tags associated with published posts.
     *
     * @param int $limit Max number of tags to return.
     * @return array<int|string, \Cake\Datasource\EntityInterface>
     */
    public function getPopularTags(int $limit = 20): array
    {
        $table = TableRegistry::getTableLocator()->get('BlogTags');
        $tags = $table->find()
            ->innerJoinWith('BlogPosts', function ($q) {
                return $q->where(['BlogPosts.is_published' => true]);
            })
            ->group(['BlogTags.id', 'BlogTags.name', 'BlogTags.slug'])
            ->select([
                'BlogTags.id',
                'BlogTags.name',
                'BlogTags.slug',
                'post_count' => $table->find()->func()->count('BlogPosts.id'),
            ])
            ->orderByDesc('post_count')
            ->limit($limit)
            ->all()
            ->toArray();

        return $tags;
    }

    /**
     * Get all posts for admin listing.
     *
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    public function getAllPosts(): array
    {
        $table = $this->posts();

        $rows = $table->find()
            ->contain(['BlogTags'])
            ->orderByDesc('BlogPosts.created')
            ->all()
            ->toArray();

        return $this->normalizePosts($rows);
    }

    /**
     * Create a blog post and apply tags.
     *
     * @param array $data
     * @return \App\Model\Entity\BlogPost|false
     */
    public function createPost(array $data): BlogPost|false
    {
        $table = $this->posts();
        $data = $this->normalizeData($data, $table);

        $entity = $table->newEntity($data, ['associated' => ['BlogTags']]);
        $saved = $table->save($entity);
        if ($saved === false) {
            return false;
        }

        assert($saved instanceof BlogPost);

        $this->tagging->applyFromData((int)$saved->id, $data);

        return $this->getPostById((int)$saved->id) ?: $saved;
    }

    /**
     * Update an existing blog post and reapply tags.
     *
     * @param int $id
     * @param array $data
     * @return \App\Model\Entity\BlogPost|false
     */
    public function updatePost(int $id, array $data): BlogPost|false
    {
        $table = $this->posts();
        $entity = $table->get($id, contain: ['BlogTags']);
        $data = $this->normalizeData($data, $table, $id);

        $entity = $table->patchEntity($entity, $data, ['associated' => ['BlogTags']]);
        $saved = $table->save($entity);
        if ($saved === false) {
            return false;
        }

        assert($saved instanceof BlogPost);

        $this->tagging->applyFromData((int)$id, $data);

        return $this->getPostById($id) ?: $saved;
    }

    /**
     * Delete a blog post.
     *
     * @param int $id
     */
    public function deletePost(int $id): bool
    {
        $table = $this->posts();
        $entity = $table->get($id);

        return (bool)$table->delete($entity);
    }

    /**
     * Get label for UI display.
     *
     * @param int $id
     */
    public function getDisplayLabel(int $id): string
    {
        $post = $this->posts()->find()->select(['id', 'title'])->where(['id' => $id])->first();

        if (!$post) {
            return 'Post #' . $id;
        }

        $title = $post->get('title');

        return is_scalar($title) ? (string)$title : 'Post #' . $id;
    }

    /**
     * @param array<int|string,mixed> $rows
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    private function normalizePosts(array $rows): array
    {
        /** @var array<int,\App\Model\Entity\BlogPost> $posts */
        $posts = array_values(array_filter($rows, static fn($row): bool => $row instanceof BlogPost));

        return $posts;
    }

    /**
     * Normalize incoming data (slug, publish dates, status).
     *
     * @param array $data
     * @param \Cake\ORM\Table $table
     * @param int|null $ignoreId
     * @return array<string,mixed>
     */
    private function normalizeData(array $data, Table $table, ?int $ignoreId = null): array
    {
        $normalized = $data;
        $title = (string)($normalized['title'] ?? '');
        $slugInput = (string)($normalized['slug'] ?? '');

        $slug = $slugInput !== '' ? $slugInput : ($title !== '' ? Text::slug($title) : '');
        if ($slug !== '') {
            $slug = mb_strtolower($slug);
        }
        if ($slug !== '') {
            $slug = $this->uniqueSlug($slug, $table, $ignoreId);
        }
        if ($slug !== '') {
            $normalized['slug'] = $slug;
        }

        $isPublished = (bool)($normalized['is_published'] ?? false);
        $normalized['status'] = $isPublished ? 'published' : 'draft';

        $isPinned = (bool)($normalized['is_pinned'] ?? false);
        if (!$isPinned) {
            $normalized['pinned_rank'] = null;
            $normalized['pinned_until'] = null;
        }

        $publishedAt = $normalized['published_at'] ?? null;
        $publishedAtInstance = null;
        if ($publishedAt instanceof DateTimeInterface) {
            $publishedAtInstance = $publishedAt instanceof DateTimeImmutable
                ? $publishedAt
                : DateTimeImmutable::createFromMutable($publishedAt);
        } elseif (is_string($publishedAt) && $publishedAt !== '') {
            try {
                $publishedAtInstance = new DateTimeImmutable($publishedAt);
            } catch (Exception) {
                $publishedAtInstance = null;
            }
        }

        if ($isPublished) {
            if ($publishedAtInstance === null) {
                $normalized['published_at'] = new DateTimeImmutable();
            }
        } elseif ($publishedAtInstance !== null) {
            $now = new DateTimeImmutable();
            if ($publishedAtInstance <= $now) {
                $normalized['published_at'] = null;
            }
        }

        return $normalized;
    }

    /**
     * Ensure slug uniqueness with numeric suffix.
     *
     * @param string $slug
     * @param \Cake\ORM\Table $table
     * @param int|null $ignoreId
     */
    private function uniqueSlug(string $slug, Table $table, ?int $ignoreId = null): string
    {
        $base = mb_strtolower(Text::slug($slug) ?: 'post');
        $candidate = $base;
        $suffix = 2;
        $conditions = ['slug' => $candidate];
        if ($ignoreId !== null) {
            $conditions['id !='] = $ignoreId;
        }

        while ($table->exists($conditions)) {
            $candidate = $base . '-' . $suffix;
            $conditions['slug'] = $candidate;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Resolve the BlogPosts table locator.
     *
     * @return \Cake\ORM\Table
     */
    private function posts(): Table
    {
        return TableRegistry::getTableLocator()->get('BlogPosts');
    }
}
