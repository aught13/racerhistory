<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BlogPostsAdminService;
use Cake\Http\Response;

/**
 * Admin Blog Posts Controller
 *
 * Thin HTTP orchestrator for BlogPost CRUD in the admin UI.
 *
 * All ORM and form-data assembly are delegated to BlogPostsAdminService.
 * The controller owns request-method guards, flash messages, redirects,
 * and template selection only.
 *
 * @property \App\Service\BlogPostsAdminService $blogPostsAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class BlogPostsController extends AppController
{
    /**
     * @var \App\Service\BlogPostsAdminService
     */
    private BlogPostsAdminService $blogPostsAdminService;

    /**
     * Controller initialize: setup BlogPostService.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->blogPostsAdminService = new BlogPostsAdminService();
    }

    /**
     * Admin index listing.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $posts = $this->blogPostsAdminService->getIndexPosts();
        $this->set(compact('posts'));
    }

    /**
     * Add new post.
     */
    public function add(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $post = $this->blogPostsAdminService->newEntity();

        if ($this->request->is('post')) {
            $data = (array)$this->request->getData();
            $result = $this->blogPostsAdminService->add($data);
            if ($result['success']) {
                $this->Flash->success('The blog post has been saved.');

                return $this->redirect(['action' => 'edit', (int)$result['createdId']]);
            }
            $this->Flash->error('The blog post could not be saved. Please, try again.');
            $post = $result['post'];
        }

        $this->set($this->blogPostsAdminService->buildFormViewData($post));
        $this->viewBuilder()->setTemplate('edit');

        return null;
    }

    /**
     * Edit post.
     */
    public function edit(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post', 'put', 'patch']);
        $post = $this->blogPostsAdminService->getEditEntity($id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = (array)$this->request->getData();
            $result = $this->blogPostsAdminService->edit($id, $data);
            if ($result['success']) {
                $this->Flash->success('The blog post has been saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }
            $this->Flash->error('The blog post could not be saved. Please, try again.');
            $post = $result['post'];
        }

        $this->set($this->blogPostsAdminService->buildFormViewData($post));

        return null;
    }

    /**
     * Delete post.
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->blogPostsAdminService->delete($id)) {
            $this->Flash->success('The blog post has been deleted.');
        } else {
            $this->Flash->error('The blog post could not be deleted. Please, try again.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
