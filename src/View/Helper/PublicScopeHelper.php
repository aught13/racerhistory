<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\PublicSportScopeService;
use Cake\Routing\Router;
use Cake\View\Helper;

/**
 * Builds public route URLs while preserving the normalized browse scope.
 */
class PublicScopeHelper extends Helper
{
    /**
     * @param array<string,mixed> $url Cake route URL.
     * @param array<string,mixed>|null $scopeOverride Optional explicit sport/team scope.
     * @return string Scoped URL.
     */
    public function url(array $url, ?array $scopeOverride = null): string
    {
        return Router::url($this->withScope($url, $scopeOverride));
    }

    /**
     * Add normalized sport/team scope to a route URL while preserving page filters.
     *
     * @param array<string,mixed> $url Cake route URL.
     * @param array<string,mixed>|null $scopeOverride Optional explicit sport/team scope.
     * @return array<string,mixed>
     */
    public function withScope(array $url, ?array $scopeOverride = null): array
    {
        $scope = $scopeOverride ?? $this->getView()->get('publicScope');
        if (!is_array($scope) || !array_key_exists('sportKey', $scope)) {
            $scope = (new PublicSportScopeService())->resolve($this->getView()->getRequest()->getQueryParams());
        }

        if (empty($scope['valid']) && array_key_exists('valid', $scope)) {
            return $url;
        }

        $scopeQuery = [
            'sport' => $scope['sportKey'] ?? 'all',
            'team' => isset($scope['teamId']) ? (string)$scope['teamId'] : 'all',
        ];
        $routeQuery = $url['?'] ?? [];
        if (!is_array($routeQuery)) {
            $routeQuery = [];
        }
        $url['?'] = array_merge($routeQuery, $scopeQuery);

        return $url;
    }
}
