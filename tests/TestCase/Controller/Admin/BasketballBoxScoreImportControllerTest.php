<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Service\BasketballBoxScoreImportService;
use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ReflectionClass;

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
        'app.GameEav',
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
            'team_box_selected',
            'opponent_box_selected',
            'period_boxes',
            'period_boxes_selected',
            'game_results',
            'game_results_selected',
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
     * Test a PDF upload is extracted before preview parsing.
     *
     * @return void
     */
    public function testPreviewExtractsPdfUpload(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-controller-pdf-');
        self::assertNotFalse($path);
        file_put_contents($path, $this->buildPdf('not a box score'));

        try {
            $this->post('/admin/basketball-box-score-import/index/1', [
                'intent' => 'preview',
                'pdf_file' => [
                    'tmp_name' => $path,
                    'size' => filesize($path),
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'box-score.pdf',
                    'type' => 'application/pdf',
                ],
            ]);

            $this->assertResponseOk();
            $this->assertResponseContains('does not look like an NCAA LiveStats box score');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
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
            $this->assertResponseContains('name="team_rows[0][import]"');
            $this->assertResponseContains('name="source_type" value="csv"');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Keep sparse official slots and skip periods that have no database mapping.
     *
     * @return void
     */
    public function testBuildPreviewPreservesOfficialSlotAndSkipsUnknownPeriod(): void
    {
        $service = new BasketballBoxScoreImportService();
        $parsed = [
            'date' => null,
            'teams' => [
                ['label' => 'Team', 'score' => null, 'players' => [], 'totals' => []],
                ['label' => 'Opponent', 'score' => null, 'players' => [], 'totals' => []],
            ],
            'game_results' => [
                'attendance' => null,
                'officials' => ['official_2' => 'Referee B'],
                'period_scores' => ['unsupported' => ['team' => 1, 'opponent' => 2]],
            ],
            'period_boxes' => [],
        ];
        $buildPreview = (new ReflectionClass(BasketballBoxScoreImportService::class))
            ->getMethod('buildPreview');

        $preview = $buildPreview->invoke($service, 1, $parsed, '');

        self::assertSame('Referee B', $preview['gameResults']['official_2']);
        self::assertArrayNotHasKey('period_unsupported_team', $preview['gameResults']);
    }

    /**
     * Preview optional game-result and period fields from a legacy box score.
     *
     * @return void
     */
    public function testPreviewShowsOptionalSupplementalFields(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score -- Game Totals
VISITORS: Los Angeles Lakers
Totals.............. 1-2 0-0 0-0 0 0 0 0 2 0 0 0 0 200
HOME TEAM: Boston Celtics
Totals.............. 2-4 0-0 0-0 0 0 0 0 4 0 0 0 0 200
Officials: Referee A, Referee B, Referee C
Attendance: 1957
Score by Periods 1st 2nd Total
Los Angeles Lakers........... 1 1 - 2
Boston Celtics................ 2 2 - 4
Points in the paint-LAL 1,BOS 2. Points off turnovers-LAL 2,BOS 3.
PERIOD_DETAIL|0|ORB|1=2
PERIOD_DETAIL|0|RB|1=6
TEXT;
        $this->post('/admin/basketball-box-score-import/index/1', [
            'intent' => 'preview',
            'source_type' => 'text',
            'raw_text' => $text,
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('name="team_box_selected[PNT]"');
        $this->assertResponseContains('name="period_boxes_selected[team_1][PTS]"');
        $this->assertResponseContains('name="period_boxes[team_1][DRB]" value="4"');
        $this->assertResponseContains('name="game_results_selected[attendance]"');
        $this->assertResponseContains('name="game_results[official_1]"');
    }

    /**
     * Map an HTML third period to overtime for a two-period basketball game.
     *
     * @return void
     */
    public function testPreviewMapsThirdPeriodToOvertime(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score -- Game Totals
VISITORS: Los Angeles Lakers
Totals.............. 10-20 1-4 5-6 3 7 10 8 40 10 10 0 1 0 200
HOME TEAM: Boston Celtics
Totals.............. 9-20 2-6 3-4 4 6 10 9 38 10 10 0 1 0 200
Score by Periods 1st 2nd 3rd Total
Los Angeles Lakers........... 20 15 5 - 40
Boston Celtics................ 18 17 3 - 38
TEXT;
        $this->post('/admin/basketball-box-score-import/index/1', [
            'intent' => 'preview',
            'source_type' => 'text',
            'raw_text' => $text,
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('name="game_results[overtime_1_team]" value="5"');
        $this->assertResponseContains('name="game_results[overtime_1_opponent]" value="3"');
        $this->assertResponseContains('name="period_boxes_selected[team_OT][PTS]"');
        $this->assertResponseNotContains('name="game_results[period_4_team]"');
    }

    /**
     * Import only selected box-score and game-result fields, preserving unchecked values.
     *
     * @return void
     */
    public function testSaveImportsPartialSelectedGameData(): void
    {
        $this->post('/admin/basketball-box-score-import/index/1', [
            'intent' => 'save',
            'team_box' => ['PNT' => '8', 'FGM' => '99'],
            'team_box_selected' => ['PNT' => '1'],
            'period_boxes' => ['team_1' => ['PTS' => '32', 'FGM' => '99']],
            'period_boxes_selected' => ['team_1' => ['PTS' => '1']],
            'game_results' => [
                'attendance' => '1957',
                'period_1_team' => '32',
                'period_1_opponent' => '51',
                'official_1' => 'Imported Referee',
            ],
            'game_results_selected' => [
                'attendance' => '1',
                'period_1_team' => '1',
                'period_1_opponent' => '1',
                'official_1' => '1',
            ],
        ]);

        $this->assertResponseCode(302);

        $games = $this->fetchTable('Games');
        $game = $games->get(1);
        self::assertSame('1957', $game->attendance);

        $boxTable = $this->fetchTable('StatBasketGameBox');
        $finalTeam = $boxTable->find()->where([
            'game_id' => 1,
            'opponent_id' => 0,
            'period' => 'Z',
        ])->firstOrFail();
        self::assertSame('8', (string)$finalTeam->get('PNT'));
        self::assertSame('28', (string)$finalTeam->get('FGM'));

        $periodTeam = $boxTable->find()->where([
            'game_id' => 1,
            'opponent_id' => 0,
            'period' => '1',
        ])->firstOrFail();
        self::assertSame('32', (string)$periodTeam->get('PTS'));
        self::assertNotSame('99', (string)$periodTeam->get('FGM'));

        $eavTable = $this->fetchTable('GameEav');
        self::assertSame('32', (string)$eavTable->find()->where(['game_id' => 1, 'key' => 'period_1_team'])->firstOrFail()->get('value'));
        self::assertSame('51', (string)$eavTable->find()->where(['game_id' => 1, 'key' => 'period_1_opponent'])->firstOrFail()->get('value'));
        self::assertSame('Imported Referee', (string)$eavTable->find()->where(['game_id' => 1, 'key' => 'official_1'])->firstOrFail()->get('value'));
        self::assertSame('Ref B', (string)$eavTable->find()->where(['game_id' => 1, 'key' => 'official_2'])->firstOrFail()->get('value'));
    }

    /**
     * Import selected overtime box totals and game-result points.
     *
     * @return void
     */
    public function testSaveImportsOvertimeFields(): void
    {
        $this->post('/admin/basketball-box-score-import/index/2', [
            'intent' => 'save',
            'period_boxes' => ['team_OT' => ['PTS' => '5']],
            'period_boxes_selected' => ['team_OT' => ['PTS' => '1']],
            'game_results' => [
                'overtime_1_team' => '5',
                'overtime_1_opponent' => '3',
            ],
            'game_results_selected' => [
                'overtime_1_team' => '1',
                'overtime_1_opponent' => '1',
            ],
        ]);

        $this->assertResponseCode(302);

        $boxTable = $this->fetchTable('StatBasketGameBox');
        $overtimeTeam = $boxTable->find()->where([
            'game_id' => 2,
            'opponent_id' => 0,
            'period' => 'OT',
        ])->firstOrFail();
        self::assertSame('5', (string)$overtimeTeam->get('PTS'));

        $eavTable = $this->fetchTable('GameEav');
        self::assertSame('5', (string)$eavTable->find()->where(['game_id' => 2, 'key' => 'overtime_1_team'])->firstOrFail()->get('value'));
        self::assertSame('3', (string)$eavTable->find()->where(['game_id' => 2, 'key' => 'overtime_1_opponent'])->firstOrFail()->get('value'));
    }

    /**
     * Build a small text-bearing PDF without adding a binary fixture.
     *
     * @param string $text PDF text
     * @return string PDF bytes
     */
    private function buildPdf(string $text): string
    {
        $stream = 'BT /F1 12 Tf 72 720 Td (' . $text . ') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($index = 1; $index <= 5; $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }
}
