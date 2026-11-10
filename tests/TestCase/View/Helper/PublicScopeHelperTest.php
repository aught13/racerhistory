<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\AppView;
use App\View\Helper\PublicScopeHelper;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class PublicScopeHelperTest extends TestCase
{
    /**
     * @return void
     */
    public function testUrlPreservesNormalizedScopeAndPageFilters(): void
    {
        $request = new ServerRequest([
            'url' => '/games/ranked',
            'query' => ['sport' => 'football', 'team' => '3'],
        ]);
        $view = new AppView($request);
        $view->set('publicScope', [
            'valid' => true,
            'sportKey' => 'football',
            'teamId' => 3,
        ]);
        $helper = new PublicScopeHelper($view);

        $url = $helper->withScope([
            'controller' => 'Games',
            'action' => 'ranked',
            '?' => ['filter' => 'team'],
        ]);

        $this->assertSame([
            'controller' => 'Games',
            'action' => 'ranked',
            '?' => [
                'filter' => 'team',
                'sport' => 'football',
                'team' => '3',
            ],
        ], $url);
    }

    /**
     * @return void
     */
    public function testExplicitScopeOverrideReplacesCurrentScope(): void
    {
        $view = new AppView(new ServerRequest(['url' => '/people/1']));
        $view->set('publicScope', [
            'valid' => true,
            'sportKey' => 'basketball',
            'teamId' => 1,
        ]);
        $helper = new PublicScopeHelper($view);

        $url = $helper->withScope(
            ['controller' => 'Seasons', 'action' => 'view', 3],
            ['sportKey' => 'football', 'teamId' => 3],
        );

        $this->assertSame('football', $url['?']['sport']);
        $this->assertSame('3', $url['?']['team']);
    }
}
