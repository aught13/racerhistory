<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BlogPostsAdminService;
use App\Service\GameService;
use App\Service\ImagesAdminService;
use App\Service\OpponentService;
use App\Service\SiteService;
use App\Service\TaggingService;
use App\Service\TeamSeasonService;
use App\Service\TeamService;
use App\Service\TeamSportContextService;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * Admin Tags Controller
 *
 * Provides a small HTTP adapter for rendering a tag-selection modal and
 * applying tags for different subject types (images, blog posts).
 */
class TagsController extends AppController
{
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
     * URL: /admin/tags/modal/{subject}/{id}
     *
     * @param string $subject
     * @param int $id
     */
    public function modal(string $subject, int $id = 0): ?Response
    {
        $this->request->allowMethod(['get']);

        $subject = strtolower((string)$subject);

        if ($subject === 'images' || $subject === 'image') {
            $data = (new ImagesAdminService())->getTagsPageData($id);
            // Provide a canonical subject for form action construction
            // Unpack the data array into view variables for the modal template
            if (!empty($data)) {
                $this->set($data);
            }
            $this->set('subject', 'images');
            $this->set('subjectId', $id);
            // Render only the modal fragment for AJAX injection
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Admin/Tags');
            $this->viewBuilder()->setTemplate('modal');

            return null;
        }

        if ($subject === 'blogposts' || $subject === 'blog-posts' || $subject === 'blog_post' || $subject === 'post') {
            $service = new BlogPostsAdminService();
            $post = $id > 0 ? $service->getEditEntity($id) : $service->newEntity();
            $viewData = $service->buildFormViewData($post);
            $this->set($viewData);
            $this->set('post', $post);
            $this->set('subject', 'blogposts');
            $this->set('subjectId', $id);
            // Render only the modal fragment for AJAX injection
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Admin/Tags');
            $this->viewBuilder()->setTemplate('modal');

            return null;
        }

        // Fallback: render an empty modal with lookup lists only
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

        $this->set(compact('teams', 'teamSeasons', 'games', 'sites', 'opponents', 'sports'));
        $this->set('currentTags', []);
        $this->set('tagString', '');
        $this->set('subject', $subject);
        $this->set('subjectId', $id);
        // Render only the modal fragment for AJAX injection
        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setTemplatePath('Admin/Tags');
        $this->viewBuilder()->setTemplate('modal');

        return null;
    }

    /**
     * Apply tags for a given subject via POST and return JSON result.
     * URL: /admin/tags/apply/{subject}/{id}
     *
     * @param string $subject
     * @param int $id
     */
    public function apply(string $subject, int $id): Response
    {
        $this->request->allowMethod(['post']);

        $subject = strtolower((string)$subject);

        $data = (array)$this->request->getData();

        if ($subject === 'images' || $subject === 'image') {
            $svc = TaggingService::forImages();
            $applied = $svc->applyFromData($id, $data);
            // Fetch human-friendly names for applied slugs so the client can
            // render badges without reloading the page. Also return the
            // limited set of form fields so parent forms (e.g. bulk upload)
            // can be updated with the selection.
            $tags = [];
            if ($applied !== []) {
                $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
                $rows = $tagsTable->find()->select(['slug', 'name'])->where(['slug IN' => $applied])->all();
                foreach ($rows as $r) {
                    $tags[] = ['slug' => (string)$r->get('slug'), 'name' => (string)$r->get('name')];
                }
            }

            $formKeys = [
                'person_select',
                'team_select',
                'teamseason_select',
                'game_select', 'site_select',
                'opponent_select',
                'sport_select',
                'roster_select',
                'tags',
                ];
            $formFields = array_intersect_key($data, array_flip($formKeys));

            $this->response = $this->response->withType('application/json');

            return $this->response->withStringBody(json_encode([
                'success' => true,
                'applied' => $applied,
                'tags' => $tags,
                'formFields' => $formFields,
            ]));
        }

        if (
            $subject === 'blogposts' ||
            $subject === 'blog-posts' ||
            $subject === 'blog_post' ||
            $subject === 'post'
        ) {
            $svc = TaggingService::forBlogPosts();
            $applied = $svc->applyFromData($id, $data);
            // For blogposts, also include human-friendly tag names.
            $tags = [];
            if ($applied !== []) {
                $tagsTable = TableRegistry::getTableLocator()->get('BlogTags');
                $rows = $tagsTable->find()->select(['slug', 'name'])->where(['slug IN' => $applied])->all();
                foreach ($rows as $r) {
                    $tags[] = ['slug' => (string)$r->get('slug'), 'name' => (string)$r->get('name')];
                }
            }

            $formKeys = [
                'person_select',
                'team_select',
                'teamseason_select',
                'game_select',
                'site_select',
                'opponent_select',
                'sport_select',
                'roster_select',
                'tags',
                ];
            $formFields = array_intersect_key($data, array_flip($formKeys));

            $this->response = $this->response->withType('application/json');

            return $this->response->withStringBody(json_encode([
                'success' => true,
                'applied' => $applied,
                'tags' => $tags,
                'formFields' => $formFields,
            ]));
        }

        $this->response = $this->response->withType('application/json');

        return $this->response->withStringBody(json_encode(['success' => false]));
    }
}
