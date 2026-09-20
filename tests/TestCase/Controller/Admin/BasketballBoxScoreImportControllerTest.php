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
            'csv_file',
            'raw_text',
            'source_type',
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

    /**
     * Test the structured CSV template downloads for the selected game.
     *
     * @return void
     */
    public function testCsvTemplateDownloads(): void
    {
        $this->get('/admin/basketball-box-score-import/csv-template/1');

        $this->assertResponseOk();
        $this->assertResponseContains('row_type,side,jersey,name,MIN');
        $this->assertResponseContains('player,team');
    }

    /**
     * Test a completed CSV preview uses the standard editable import screen.
     *
     * @return void
     */
    public function testPreviewParsesCompletedCsvTemplate(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-box-score-csv-');
        self::assertNotFalse($path);
        $csv = <<<'CSV'
row_type,side,jersey,name,MIN,FGM,FGA,TPM,TPA,FTM,FTA,ORB,DRB,RB,PF,FD,PTS,AST,TRN,STL,BS,BD
player,team,10,Player One,25,4,8,1,3,2,2,1,2,3,2,,11,3,1,2,0,
totals,team,,,200,4,8,1,3,2,2,1,2,3,2,,11,3,1,2,0,
player,opponent,20,Player Two,30,5,10,2,4,1,1,2,4,6,3,,13,4,2,1,1,
totals,opponent,,,200,5,10,2,4,1,1,2,4,6,3,,13,4,2,1,1,
CSV;
        file_put_contents($path, $csv);

        try {
            $this->post('/admin/basketball-box-score-import/index/1', [
                'intent' => 'preview',
                'csv_file' => [
                    'tmp_name' => $path,
                    'size' => strlen($csv),
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'box-score.csv',
                    'type' => 'text/csv',
                ],
            ]);

            $this->assertResponseOk();
            $this->assertResponseContains('Team player rows');
            $this->assertResponseContains('Player One');
            $this->assertResponseContains('Player Two');
            $this->assertResponseContains('name="source_type" value="csv"');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
