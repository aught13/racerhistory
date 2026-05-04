<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\SportStatsController
 */
class SportStatsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Sports',
        'app.SportStatRegistry',
        'app.SportConfigs',
    ];

    /**
     * Tests index displays registry list.
     */
    public function testIndexDisplaysRegistryList(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sport-stats');

        $this->assertResponseOk();
        $this->assertResponseContains('Sport Statistics Registry');
    }

    /**
     * Tests index filters by sport id.
     */
    public function testIndexFiltersBySportId(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sport-stats/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Basketball');
    }

    /**
     * Tests add post creates new registry.
     */
    public function testAddPostCreatesNewRegistry(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $table = $this->getTableLocator()->get('SportStatRegistry');
        $count = $table->find()->count();

        $this->post('/admin/sport-stats/add', [
            'sport_id' => 1,
            'context' => 'season',
            'entity_type' => 'team',
            'display_name' => 'Season Team Stats',
            'table_name' => 'stat_basket_season_team',
            'primary_key' => 'id',
            'fields' => ['PTS'],
            'labels' => ['Points'],
        ]);

        $this->assertRedirect([
            'prefix' => 'Admin',
            'controller' => 'SportStats',
            'action' => 'index',
            1,
        ]);

        $this->assertEquals($count + 1, $table->find()->count());
    }

    /**
     * Tests add post validation failure keeps form.
     */
    public function testAddPostValidationFailureKeepsForm(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $table = $this->getTableLocator()->get('SportStatRegistry');
        $count = $table->find()->count();

        $this->post('/admin/sport-stats/add', [
            'sport_id' => 1,
            'context' => 'game',
            'entity_type' => 'team',
            'display_name' => 'Incomplete',
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('The stat table configuration could not be saved. Please try again.');
        $this->assertEquals($count, $table->find()->count());
    }

    /**
     * Tests edit post updates field mapping.
     */
    public function testEditPostUpdatesFieldMapping(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/sport-stats/edit/1', [
            'sport_id' => 1,
            'context' => 'game',
            'entity_type' => 'team',
            'display_name' => 'Game Team Stats (Updated)',
            'table_name' => 'stat_basket_game_team',
            'fields' => ['FTM'],
            'labels' => ['Free Throws Made'],
        ]);

        $this->assertRedirect([
            'prefix' => 'Admin',
            'controller' => 'SportStats',
            'action' => 'view',
            1,
        ]);

        $record = $this->getTableLocator()->get('SportStatRegistry')->get(1);
        $mapping = json_decode((string)$record->field_mapping, true);
        $this->assertArrayHasKey('FTM', $mapping);
        $this->assertSame('Free Throws Made', $mapping['FTM']);
    }

    /**
     * Tests delete removes registry.
     */
    public function testDeleteRemovesRegistry(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->delete('/admin/sport-stats/delete/1');

        $this->assertRedirect([
            'prefix' => 'Admin',
            'controller' => 'SportStats',
            'action' => 'index',
            1,
        ]);

        $exists = $this->getTableLocator()->get('SportStatRegistry')->exists(['id' => 1]);
        $this->assertFalse($exists);
    }
}
