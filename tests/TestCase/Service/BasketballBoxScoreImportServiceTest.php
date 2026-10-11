<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Game;
use App\Model\Table\GamesTable;
use App\Service\BasketballBoxScoreImportService;
use App\Service\BasketballStatsAdminService;
use App\Service\GameService;
use Cake\ORM\Locator\LocatorInterface;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class BasketballBoxScoreImportServiceTest extends TestCase
{
    /**
     * Extract text from a valid uploaded PDF.
     *
     * @return void
     */
    public function testExtractPdfText(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-pdf-test-');
        self::assertNotFalse($path);
        file_put_contents($path, $this->buildPdf('LiveStats PDF'));

        try {
            $file = new UploadedFile(
                $path,
                filesize($path),
                UPLOAD_ERR_OK,
                'box-score.pdf',
                'application/pdf',
            );
            $text = (new BasketballBoxScoreImportService())->extractPdfText($file);

            self::assertStringContainsString('LiveStats PDF', $text);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Reject uploads that are not PDFs.
     *
     * @return void
     */
    public function testRejectsNonPdfUpload(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-not-pdf-');
        self::assertNotFalse($path);
        file_put_contents($path, 'not a PDF');

        try {
            $file = new UploadedFile($path, 9, UPLOAD_ERR_OK, 'score.txt', 'text/plain');
            $this->expectException(InvalidArgumentException::class);
            (new BasketballBoxScoreImportService())->extractPdfText($file);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Read a valid structured CSV upload and expose the fallback template.
     *
     * @return void
     */
    public function testExtractCsvTextAndTemplate(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-csv-test-');
        self::assertNotFalse($path);
        $csv = "row_type,side,jersey,name,MIN,FGM,FGA,TPM,TPA,FTM,FTA,ORB,DRB,RB,PF,FD,PTS,AST,TRN,STL,BS,BD\n";
        file_put_contents($path, $csv);

        try {
            $file = new UploadedFile($path, strlen($csv), UPLOAD_ERR_OK, 'box-score.csv', 'text/csv');
            $service = new BasketballBoxScoreImportService();

            self::assertSame($csv, $service->extractCsvText($file));
            self::assertStringStartsWith('row_type,side,jersey,name,MIN', $service->getCsvTemplate());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Serialize modern tables with regulation, overtime, and detailed team rows.
     *
     * @return void
     */
    public function testSerializesModernHtmlPeriodAndDetailTables(): void
    {
        $html = <<<'HTML'
<html><body>
<table>
<tr><th>Team</th><th>1st half1</th><th>2nd half2</th><th>OT</th><th>Total</th></tr>
<tr><td>Team A</td><td>30</td><td>28</td><td>10</td><td>68</td></tr>
<tr><td>Team B</td><td>25</td><td>20</td><td>8</td><td>53</td></tr>
</table>
<table><tr><th>#</th><th>Player</th></tr></table>
<table>
<tr><th>Team Summary</th><th>FG</th><th>3PT</th><th>FT</th></tr>
<tr><td>1st Half</td><td>15-30</td><td>4-10</td><td>5-8</td></tr>
<tr><td>OT</td><td>5-8</td><td>2-6</td><td>3-4</td></tr>
<tr><td>Total</td><td>35-68</td><td>10-30</td><td>8-12</td></tr>
<tr><td></td></tr>
</table>
<table><tr><th>#</th><th>Player</th></tr></table>
<table>
<tr><th>Team Summary</th><th>FG</th><th>3PT</th><th>FT</th></tr>
<tr><td>1st Half</td><td>12-25</td><td>3-9</td><td>5-7</td></tr>
<tr><td>OT</td><td>4-8</td><td>1-3</td><td>3-4</td></tr>
</table>
<table>
<tr><th>-</th><th>1</th><th>2</th><th>OT</th><th>T</th></tr>
<tr><td>Unrecognized detail section</td></tr>
<tr><th>Points in the Paint</th></tr>
<tr><td>Other Team</td><td>99</td><td>99</td><td>99</td><td>99</td></tr>
<tr><td>Team A</td><td>10</td><td>12</td><td>4</td><td>26</td></tr>
<tr><td>Team B</td><td>8</td><td>9</td><td>3</td><td>20</td></tr>
<tr><th>Field goals</th></tr>
<tr><td>Team A</td><td>15 - 30</td><td>15 - 30</td><td>5 - 8</td><td>35 - 68</td></tr>
<tr><td>Team B</td><td>12 - 25</td><td>12 - 24</td><td>4 - 8</td><td>28 - 57</td></tr>
<tr><th>Assists</th></tr>
<tr><td>Team A</td><td></td><td>5</td><td>0</td><td>5</td></tr>
<tr><td>Team B</td><td>4</td><td>3</td><td>1</td><td>8</td></tr>
</table>
Attendance: 1234
</body></html>
HTML;

        $text = $this->serializeHtmlBoxScoreTables($html);

        self::assertStringContainsString('PERIOD_SCORE_HEADERS|1st half1|2nd half2|OT|Total', $text);
        self::assertStringContainsString('PERIOD_SCORE|Team A|30|28|10|68', $text);
        self::assertStringContainsString('PERIOD_BOX|0|OT|5-8|2-6|3-4', $text);
        self::assertStringContainsString('PERIOD_DETAIL|0|PNT|1=10|2=12|OT=4', $text);
        self::assertStringContainsString('FINAL_DETAIL|0|PNT|26', $text);
        self::assertStringContainsString('PERIOD_DETAIL|0|FG|1=15 - 30|2=15 - 30|OT=5 - 8', $text);
        self::assertStringContainsString('Attendance: 1234', $text);
    }

    /**
     * Preserve every non-empty preformatted section from older static pages.
     *
     * @return void
     */
    public function testSerializesAllLegacyPreformattedSections(): void
    {
        $text = $this->serializeHtmlBoxScoreTables(
            '<html><body><pre>Final box</pre><pre> </pre><pre>First-half box</pre></body></html>',
        );

        self::assertSame("Final box\nFirst-half box", $text);
    }

    /**
     * Ignore HTML score tables that do not contain two team rows.
     *
     * @return void
     */
    public function testSerializesMalformedHtmlScoreTableAsEmpty(): void
    {
        $html = '<table><tr><td>Invalid score table</td></tr></table>'
            . '<table></table><table></table><table></table>';

        self::assertSame('', $this->serializeHtmlBoxScoreTables($html));

        $partialHtml = <<<'HTML'
<html><body>
<table>
<tr><th>Team</th><th>1st half1</th><th>2nd half2</th><th>Total</th></tr>
<tr><td>Team A</td><td>2</td><td>3</td><td>5</td></tr>
<tr><td>Team B</td><td>1</td><td>2</td><td>3</td></tr>
</table>
<table><tr><th>#</th><th>Player</th></tr></table>
<table><tr><td>1st Half</td><td>1-3</td><td>0-1</td><td>2-2</td></tr></table>
<table><tr><th>#</th><th>Player</th></tr></table>
</body></html>
HTML;

        self::assertStringContainsString(
            'PERIOD_SCORE|Team A|2|3|5',
            $this->serializeHtmlBoxScoreTables($partialHtml),
        );
    }

    /**
     * Reject uploads that cannot be identified as CSV files.
     *
     * @return void
     */
    public function testRejectsNonCsvUpload(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rh-not-csv-');
        self::assertNotFalse($path);
        file_put_contents($path, 'not CSV');

        try {
            $file = new UploadedFile($path, 7, UPLOAD_ERR_OK, 'score.txt', 'text/plain');
            $this->expectException(InvalidArgumentException::class);
            (new BasketballBoxScoreImportService())->extractCsvText($file);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Normalize LiveStats minutes before delegating rows to persistence.
     *
     * @return void
     */
    public function testSaveNormalizesPlayerMinutes(): void
    {
        $statsService = $this->createMock(BasketballStatsAdminService::class);
        $statsService->expects(self::once())
            ->method('saveAdminGamePersonRows')
            ->with(
                1,
                self::callback(static fn(array $rows): bool => $rows[0]['GS'] === '1' && $rows[0]['MIN'] === '24.15'),
                false,
            )
            ->willReturn(['saved' => 1, 'skipped' => 0, 'errors' => [], 'failedRows' => []]);
        $statsService->expects(self::once())
            ->method('saveAdminGameOpponentRows')
            ->with(
                1,
                self::callback(static fn(array $rows): bool => count($rows) === 2
                    && $rows[0]['GS'] === '*'
                    && $rows[1]['GS'] === 'G'
                    && $rows[0]['MIN'] === '35.60'),
            )
            ->willReturn(['saved' => 1, 'skipped' => 0, 'errors' => [], 'failedRows' => []]);
        $statsService->expects(self::once())
            ->method('saveAdminGameBox')
            ->willReturn(['success' => true, 'game' => null, 'redirectToPeriods' => false]);

        $result = (new BasketballBoxScoreImportService(null, $statsService))->save(1, [
            'team_rows' => [[
                'import' => '1',
                'team_season_roster_id' => 1,
                'GS' => '1',
                'MIN' => '24:09',
                'PTS' => '8',
            ]],
            'opponent_rows' => [[
                'import' => '1',
                'name' => 'Torey Alston',
                'GS' => '*',
                'MIN' => '35:36',
                'PTS' => '26',
            ], [
                'import' => '1',
                'name' => 'Second Opponent Player',
                'GS' => 'G',
                'MIN' => '12:00',
                'PTS' => '4',
            ]],
            'team_box' => ['PTS' => '87'],
            'opponent_box' => ['PTS' => '90'],
            'team_box_selected' => ['PTS' => '1'],
            'opponent_box_selected' => ['PTS' => '1'],
        ]);

        self::assertTrue($result['success']);
    }

    /**
     * Save only checked player, final-box, and period-box line items.
     *
     * @return void
     */
    public function testSaveAcceptsPartialSelections(): void
    {
        $statsService = $this->createMock(BasketballStatsAdminService::class);
        $statsService->expects(self::once())
            ->method('saveAdminGamePersonRows')
            ->with(1, self::callback(static fn(array $rows): bool => count($rows) === 1
                && $rows[0]['team_season_roster_id'] === 1
                && !array_key_exists('import', $rows[0])), false)
            ->willReturn(['saved' => 1, 'skipped' => 0, 'errors' => [], 'failedRows' => []]);
        $statsService->expects(self::never())->method('saveAdminGameOpponentRows');
        $statsService->expects(self::once())
            ->method('saveAdminGameBox')
            ->with(1, self::callback(static fn(array $box): bool => $box['team'] === ['PTS' => '70']
                && $box['opponent'] === []))
            ->willReturn(['success' => true, 'game' => null, 'redirectToPeriods' => false]);
        $statsService->expects(self::once())
            ->method('saveAdminGameBoxPeriods')
            ->with(1, ['team_1' => ['PTS' => '32']])
            ->willReturn(['success' => true, 'errors' => []]);

        $result = (new BasketballBoxScoreImportService(null, $statsService))->save(1, [
            'team_rows' => [[
                'import' => '1',
                'team_season_roster_id' => 1,
                'PTS' => '11',
            ], [
                'team_season_roster_id' => '',
                'PTS' => '99',
            ]],
            'opponent_rows' => [[
                'name' => 'Unchecked Player',
                'PTS' => '12',
            ]],
            'team_box' => ['PTS' => '70', 'FGM' => '21'],
            'opponent_box' => ['PTS' => '80'],
            'team_box_selected' => ['PTS' => '1'],
            'period_boxes' => ['team_1' => ['PTS' => '32', 'FGM' => '9']],
            'period_boxes_selected' => ['team_1' => ['PTS' => '1']],
        ]);

        self::assertTrue($result['success']);
        self::assertSame(1, $result['saved']);
    }

    /**
     * Ignore malformed and unchecked period and game-result form data.
     *
     * @return void
     */
    public function testNormalizersIgnoreMalformedAndUnsupportedSelections(): void
    {
        $service = new BasketballBoxScoreImportService();
        $reflection = new ReflectionClass(BasketballBoxScoreImportService::class);
        $normalizePeriodBoxes = $reflection->getMethod('normalizePeriodBoxes');
        $normalizeGameResults = $reflection->getMethod('normalizeGameResults');

        self::assertSame([], $normalizePeriodBoxes->invoke($service, null, []));
        self::assertSame([], $normalizePeriodBoxes->invoke($service, [
            'scalar' => 'not a row',
            'unselected' => ['PTS' => '4'],
            'unchecked' => ['PTS' => '5'],
        ], [
            'unchecked' => ['PTS' => '0'],
        ]));
        self::assertSame([], $normalizeGameResults->invoke($service, null, []));
        self::assertSame([
            'official_1' => 'Referee A',
            'period_1_team' => '20',
        ], $normalizeGameResults->invoke($service, [
            'attendance' => '',
            'official_1' => 'Referee A',
            'official_2' => ['not scalar'],
            'period_1_team' => '20',
            'overtime_1_opponent' => '',
            'unsupported' => 'ignore me',
        ], [
            'attendance' => '1',
            'official_1' => '1',
            'official_2' => '1',
            'period_1_team' => '1',
            'overtime_1_opponent' => '1',
            'unsupported' => '1',
            'missing' => '1',
            'official_3' => '0',
        ]));
    }

    /**
     * Return box-score writer errors instead of reporting a successful partial import.
     *
     * @return void
     */
    public function testSaveCollectsFinalAndPeriodWriterErrors(): void
    {
        $statsService = $this->createMock(BasketballStatsAdminService::class);
        $statsService->expects(self::never())->method('saveAdminGamePersonRows');
        $statsService->expects(self::never())->method('saveAdminGameOpponentRows');
        $statsService->expects(self::once())
            ->method('saveAdminGameBox')
            ->willReturn(['success' => false, 'game' => null, 'redirectToPeriods' => false]);
        $statsService->expects(self::once())
            ->method('saveAdminGameBoxPeriods')
            ->willReturn(['success' => false, 'errors' => ['Team Period 1']]);

        $result = (new BasketballBoxScoreImportService(null, $statsService))->save(1, [
            'team_box' => ['PTS' => '10'],
            'team_box_selected' => ['PTS' => '1'],
            'period_boxes' => ['team_1' => ['PTS' => '5']],
            'period_boxes_selected' => ['team_1' => ['PTS' => '1']],
        ]);

        self::assertFalse($result['success']);
        self::assertSame([
            'The team box score could not be saved.',
            'Team Period 1',
        ], $result['errors']);
    }

    /**
     * Report a game-result save error when the EAV writer throws.
     *
     * @return void
     */
    public function testSaveCollectsGameResultWriterException(): void
    {
        $gameService = $this->createMock(GameService::class);
        $gameService->expects(self::once())
            ->method('saveGameEavFromRequest')
            ->with(1, ['official_1' => 'Referee A'])
            ->willThrowException(new RuntimeException('EAV write failed.'));

        $result = (new BasketballBoxScoreImportService(null, null, null, $gameService))->save(1, [
            'game_results' => ['official_1' => 'Referee A'],
            'game_results_selected' => ['official_1' => '1'],
        ]);

        self::assertFalse($result['success']);
        self::assertSame(['Game period scores or officials could not be saved.'], $result['errors']);
    }

    /**
     * Report an attendance write failure from the Games table.
     *
     * @return void
     */
    public function testSaveReportsAttendanceWriteFailure(): void
    {
        $game = new Game(['id' => 1]);
        $gamesTable = $this->createMock(GamesTable::class);
        $gamesTable->expects(self::once())->method('get')->with(1)->willReturn($game);
        $gamesTable->expects(self::once())->method('save')->with($game)->willReturn(false);

        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects(self::once())->method('get')->with('Games', [])->willReturn($gamesTable);
        $service = new BasketballBoxScoreImportService();
        $service->setTableLocator($locator);

        $result = $service->save(1, [
            'game_results' => ['attendance' => '1234'],
            'game_results_selected' => ['attendance' => '1'],
        ]);

        self::assertFalse($result['success']);
        self::assertSame(['Game attendance could not be saved.'], $result['errors']);
    }

    /**
     * Preserve explicit defensive rebounds and leave unresolvable values unchanged.
     *
     * @return void
     */
    public function testDerivedDefensiveReboundsPreserveExistingOrIncompleteValues(): void
    {
        $service = new BasketballBoxScoreImportService();
        $method = (new ReflectionClass(BasketballBoxScoreImportService::class))
            ->getMethod('derivePeriodDefensiveRebounds');

        $explicit = ['ORB' => 2, 'DRB' => 5, 'RB' => 7];
        self::assertSame($explicit, $method->invoke($service, $explicit));

        $incomplete = ['ORB' => 2, 'RB' => null];
        self::assertSame($incomplete, $method->invoke($service, $incomplete));
    }

    /**
     * Resolve an away-game source order using the opponent short name.
     *
     * @return void
     */
    public function testResolveTeamIndexUsesOpponentShortName(): void
    {
        $game = new Game([
            'team_season' => (object)[
                'team' => (object)[
                    'team_name' => "Men's Basketball",
                    'team_nickname' => 'Racers',
                    'team_scorebug' => 'MUR',
                ],
            ],
            'opponent' => (object)[
                'opponent_name' => 'Bellarmine University',
                'opponent_short' => 'Bellarmine',
                'opponent_abbr' => 'BELL',
            ],
        ]);
        $method = (new ReflectionClass(BasketballBoxScoreImportService::class))
            ->getMethod('resolveTeamIndex');

        $index = $method->invoke(new BasketballBoxScoreImportService(), [
            ['label' => 'Bellarmine'],
            ['label' => 'Murray St.'],
        ], $game);

        self::assertSame(1, $index);
    }

    /**
     * Match player jerseys independent of leading zeroes.
     *
     * @return void
     */
    public function testMatchesJerseyWithLeadingZeroes(): void
    {
        $service = new BasketballBoxScoreImportService();
        $method = (new ReflectionClass(BasketballBoxScoreImportService::class))->getMethod('matchRoster');
        $result = $method->invoke($service, ['jersey' => '04', 'name' => 'Player Name'], [[
            'id' => 4,
            'jersey' => '4',
            'name' => 'Different Name',
            'label' => '#4 Roster Player',
        ]]);

        self::assertSame(['id' => 4, 'label' => '#4 Roster Player', 'type' => 'jersey'], $result);

        $normalizeJersey = (new ReflectionClass(BasketballBoxScoreImportService::class))
            ->getMethod('normalizeJersey');
        self::assertSame('tm', $normalizeJersey->invoke($service, ' TM '));
    }

    /**
     * Map source periods after regulation to numbered overtime periods.
     *
     * @return void
     */
    public function testMapsSourcePeriodsToConfiguredOvertimeCodes(): void
    {
        $service = new BasketballBoxScoreImportService();
        $method = (new ReflectionClass(BasketballBoxScoreImportService::class))->getMethod('mapSourcePeriod');

        self::assertSame('OT', $method->invoke($service, '3', 2));
        self::assertSame('OT2', $method->invoke($service, '4', 2));
        self::assertSame('3', $method->invoke($service, '3', 4));
        self::assertSame('OT', $method->invoke($service, 'OT', 2));
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

    /**
     * Invoke the HTML serializer with a local Crawler fixture.
     *
     * @param string $html HTML source
     * @return string Serialized text
     */
    private function serializeHtmlBoxScoreTables(string $html): string
    {
        $method = (new ReflectionClass(BasketballBoxScoreImportService::class))
            ->getMethod('serializeHtmlBoxScoreTables');
        $text = $method->invoke(new BasketballBoxScoreImportService(), new Crawler($html));
        self::assertIsString($text);

        return $text;
    }
}
