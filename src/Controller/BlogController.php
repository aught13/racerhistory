<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class BlogController extends AppController
{
    private BlogPostService $blogPostService;

    /**
     * Controller initialize: load components and services.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->blogPostService = new BlogPostService();
    }

    /**
     * Skip authorization for public blog actions.
     *
     * @param \Cake\Event\EventInterface $event Event instance
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * Public blog index (published posts).
     */
    public function index(): void
    {
        $posts = $this->blogPostService->getPublishedPosts();
        $this->set(compact('posts'));
    }

    /**
     * View single post by slug.
     */
    public function view(string $slug): void
    {
        $post = $this->blogPostService->getPublishedBySlug($slug);
        if (!$post) {
            throw new NotFoundException('Post not found');
        }

        $this->set(compact('post'));
    }
}
