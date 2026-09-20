<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BasketballBoxScoreImportControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Games',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Opponents',
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.StatBasketGamePerson',
        'app.StatBasketGameBox',
        'app.Sports',
        'app.GameTypes',
        'app.Sites',
        'app.Places',
    ];

    /**
     * Set up authenticated admin importer requests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields([
            'intent',
            'pdf_file',
            'raw_text',
            'team_rows',
            'opponent_rows',
            'team_box',
            'opponent_box',
            'add_to_totals',
            'team_minutes',
        ]);
        $this->mockIdentity();
    }

    /**
     * Test the importer form renders for an authenticated admin.
     *
     * @return void
     */
    public function testIndexGetRendersImporter(): void
    {
        $this->get('/admin/basketball-box-score-import/index/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Import Basketball Box Score');
        $this->assertResponseContains('Choose the official PDF');
        $this->assertResponseContains('multipart/form-data');
        $this->assertResponseContains('data-turbo="false"');
        $this->assertResponseContains('Preview Import');
    }

    /**
     * Test invalid source text returns to the importer with a message.
     *
     * @return void
     */
    public function testPreviewRejectsUnrecognizedText(): void
    {
        $this->post('/admin/basketball-box-score-import/index/1', [
            'intent' => 'preview',
            'raw_text' => 'not a box score',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('does not look like an NCAA LiveStats box score');
    }
}
