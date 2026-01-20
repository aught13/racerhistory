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
     * Detect whether this request originates from a Turbo Frame.
     */
    private function isTurboFrameRequest(): bool
    {
        return $this->request->getHeaderLine('Turbo-Frame') !== '';
    }

    /**
     * For Turbo Frame requests, return a minimal frame-only response.
     */
    private function applyTurboFrameResponse(string $template): void
    {
        if (!$this->isTurboFrameRequest()) {
            return;
        }

        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setTemplate($template);
    }

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
        $page = (int)$this->request->getQuery('page', 1);
        $limit = (int)$this->request->getQuery('limit', 10);

        $offset = max(0, ($page - 1) * $limit);
        $result = $this->blogPostService->getPublishedPostsPage($limit, $offset);

        $posts = $result['posts'];
        $total = $result['total'];

        $this->set(compact('posts', 'page', 'limit', 'total'));

        $this->applyTurboFrameResponse('index_frame');
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

        $this->applyTurboFrameResponse('view_frame');
    }
}
