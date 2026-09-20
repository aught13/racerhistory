<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BasketballBoxScoreImportService;
use App\Service\BasketballStatsAdminService;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\TestCase;

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
                self::callback(static fn(array $rows): bool => $rows[0]['MIN'] === '24.15'),
                false,
            )
            ->willReturn(['saved' => 1, 'skipped' => 0, 'errors' => [], 'failedRows' => []]);
        $statsService->expects(self::once())
            ->method('saveAdminGameOpponentRows')
            ->with(
                1,
                self::callback(static fn(array $rows): bool => $rows[0]['MIN'] === '35.60'),
            )
            ->willReturn(['saved' => 1, 'skipped' => 0, 'errors' => [], 'failedRows' => []]);
        $statsService->expects(self::once())
            ->method('saveAdminGameBox')
            ->willReturn(['success' => true, 'game' => null, 'redirectToPeriods' => false]);

        $result = (new BasketballBoxScoreImportService(null, $statsService))->save(1, [
            'team_rows' => [[
                'team_season_roster_id' => 1,
                'MIN' => '24:09',
                'PTS' => '8',
            ]],
            'opponent_rows' => [[
                'name' => 'Torey Alston',
                'MIN' => '35:36',
                'PTS' => '26',
            ]],
            'team_box' => ['PTS' => '87'],
            'opponent_box' => ['PTS' => '90'],
        ]);

        self::assertTrue($result['success']);
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
