<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TagLookupsAdminService;
use Cake\Http\Response;

/**
 * Admin Tag Lookups Controller
 *
 * Thin JSON endpoint controller for admin autocomplete/tag widgets.
 *
 * All lookup querying and payload shaping live in TagLookupsAdminService.
 * This controller only validates request method/inputs and returns JSON.
 *
 * Endpoints:
 * - persons: person autocomplete search by `q`
 * - games: game autocomplete search by `q` with optional `teamseason_id`
 * - opponents: opponent autocomplete search by `q`
 * - sites: site autocomplete search by `q`
 * - rosters: roster lookup by `person_id`
 *
 * @property \App\Service\TagLookupsAdminService $tagLookupsAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class TagLookupsController extends AppController
{
    /**
     * @var \App\Service\TagLookupsAdminService
     */
    private TagLookupsAdminService $tagLookupsAdminService;

    /**
     * Initialize controller services.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->tagLookupsAdminService = new TagLookupsAdminService();
    }

    /**
     * Search persons for autocomplete.
     * Query param: q
     */
    public function persons(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)$this->request->getQuery('q');

        return $this->json($this->tagLookupsAdminService->persons($q));
    }

    /**
     * Search games for autocomplete.
     * Query params: q, teamseason_id (optional)
     */
    public function games(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)$this->request->getQuery('q');
        $teamSeasonId = (int)$this->request->getQuery('teamseason_id');

        return $this->json($this->tagLookupsAdminService->games($q, $teamSeasonId > 0 ? $teamSeasonId : null));
    }

    /**
     * Search opponents for autocomplete.
     * Query param: q
     */
    public function opponents(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)$this->request->getQuery('q');

        return $this->json($this->tagLookupsAdminService->opponents($q));
    }

    /**
     * Search sites for autocomplete.
     * Query param: q
     */
    public function sites(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)$this->request->getQuery('q');

        return $this->json($this->tagLookupsAdminService->sites($q));
    }

    /**
     * Return roster entries for a given person.
     * Query param: person_id
     */
    public function rosters(): Response
    {
        $this->request->allowMethod(['get']);

        $personId = (int)$this->request->getQuery('person_id');

        return $this->json($this->tagLookupsAdminService->rosters($personId));
    }

    /**
     * Return JSON response helper.
     *
     * @param array<string,mixed> $payload
     */
    private function json(array $payload): Response
    {
        return $this->response->withType('application/json')->withStringBody(json_encode($payload));
    }
}
