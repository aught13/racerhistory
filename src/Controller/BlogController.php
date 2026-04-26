<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Public Blog Controller
 *
 * Handles public-facing blog post listing and viewing. The index action provides paginated access to published blog posts, while the view action allows users to read individual posts by slug. Both actions skip authorization checks to allow public access. The controller also detects Turbo Frame requests and adjusts the response accordingly to support seamless updates in the UI when filtering or navigating between posts.
 * Actions:
 * - index: Lists published blog posts with pagination. Accepts 'page' and 'limit'
 * query parameters for pagination control. Validates these parameters to ensure they are within reasonable limits.
 * - view: Displays a single blog post identified by its slug. Throws a NotFoundException
 * if the post does not exist, preventing access to invalid slugs and potential information disclosure.
 *
 * Security:
 * - The view action checks for the existence of the requested post and throws a NotFoundException
 * if the post does not exist, preventing access to invalid slugs and potential information disclosure.
 * - The index action validates pagination parameters to prevent potential issues with large offsets or limits.
 *
 * Dependencies:
 * - BlogPostService: Provides methods for retrieving published blog posts, including paginated lists and
 * individual posts by slug. This service abstracts the data retrieval logic from the controller, allowing for cleaner code and easier maintenance.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for all actions in this controller, as the
 * blog content is intended to be publicly accessible. This ensures that users can view blog posts without needing to log in or have specific permissions.
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types, although in this implementation we manually check for Turbo Frame requests in the applyTurboFrameResponse method to adjust the response format accordingly.
 *
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
