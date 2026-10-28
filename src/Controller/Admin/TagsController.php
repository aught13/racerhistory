<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TagModalService;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Admin Tags Controller
 *
 * Provides a small HTTP adapter for rendering a tag-selection modal and
 * applying tags for different subject types (images, blog posts).
 */
class TagsController extends AppController
{
    /**
     * Create the controller with its tag modal service.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param \App\Service\TagModalService $tagModalService The tag modal service.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry.
     */
    public function __construct(
        ServerRequest $request,
        private TagModalService $tagModalService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
    }

    /**
     * Initialize the controller and unlock the 'apply' action from CSRF protection.
     */
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            if (!in_array('apply', $current, true)) {
                $current[] = 'apply';
                $this->FormProtection->setConfig('unlockedActions', $current);
            }
        }
    }

    /**
     * Render the tag modal body for a given subject.
     *
     * @param string $subject
     * @param int $id
     */
    public function modal(string $subject, int $id = 0): ?Response
    {
        $this->request->allowMethod(['get']);
        $this->set($this->tagModalService->modalViewData(
            $subject,
            $id,
            $this->request->getAttribute('identity'),
        ));
        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setTemplatePath('Admin/Tags');
        $this->viewBuilder()->setTemplate('modal');

        return null;
    }

    /**
     * Apply tags for a given subject via POST and return JSON result.
     *
     * @param string $subject
     * @param int $id
     */
    public function apply(string $subject, int $id): Response
    {
        $this->request->allowMethod(['post']);
        $payload = $this->tagModalService->apply($subject, $id, (array)$this->request->getData());
        $this->response = $this->response->withType('application/json');

        return $this->response->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
