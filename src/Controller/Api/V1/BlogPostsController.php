<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\BlogPostService;

/**
 * @property \App\Model\Table\BlogPostsTable $BlogPosts
 */
class BlogPostsController extends AppController
{
    private BlogPostService $blogPostService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->blogPostService = new BlogPostService();
    }

    /**
     * List published posts.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $posts = $this->blogPostService->getPublishedPosts();
        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id' => (int)$post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at,
                'hero_image_id' => $post->hero_image_id,
                'tags' => array_map(
                    fn($t) => [
                        'name' => $t->name ?? null,
                        'slug' => $t->slug ?? null,
                    ],
                    is_iterable($post->blog_tags ?? null) ? $post->blog_tags : [],
                ),
            ];
        }

        $this->respond([
            'data' => $data,
            'meta' => [
                'count' => count($data),
            ],
        ]);
    }

    /**
     * Get a single published post by slug.
     *
     * @param string $slug
     */
    public function view(string $slug): void
    {
        $this->request->allowMethod(['get']);

        $post = $this->blogPostService->getPublishedBySlug($slug);
        if (!$post) {
            $this->respondError('Post not found', 404);

            return;
        }

        $this->respond([
            'data' => [
                'id' => (int)$post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'published_at' => $post->published_at,
                'hero_image_id' => $post->hero_image_id,
                'tags' => array_map(
                    fn($t) => [
                        'name' => $t->name ?? null,
                        'slug' => $t->slug ?? null,
                    ],
                    is_iterable($post->blog_tags ?? null) ? $post->blog_tags : [],
                ),
            ],
        ]);
    }
}
