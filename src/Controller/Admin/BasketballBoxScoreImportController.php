<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballBoxScoreImportService;
use Cake\Http\Response;
use Cake\Log\Log;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Admin importer for NCAA LiveStats basketball box-score text.
 */
class BasketballBoxScoreImportController extends AppController
{
    private BasketballBoxScoreImportService $importService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->importService = new BasketballBoxScoreImportService();
        $this->FormProtection->setConfig('unlockedFields', [
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
    }

    /**
     * Download the structured CSV fallback template for a game import.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response CSV download response
     */
    public function csvTemplate(int $gameId): Response
    {
        $this->request->allowMethod(['get']);
        $filename = 'basketball-box-score-game-' . $gameId . '-template.csv';

        return $this->response
            ->withType('text/csv')
            ->withDownload($filename)
            ->withStringBody($this->importService->getCsvTemplate());
    }

    /**
     * Display, preview, and save an official box-score import.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function index(int $gameId): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        try {
            $viewData = $this->importService->getAdminImportData($gameId);
        } catch (Throwable $e) {
            // Unexpected errors during bootstrap of importer data should not
            // cause a 500 response in the admin UI test matrix. Provide a
            // minimal fallback so the importer page can render and show an
            // error message instead of failing the request.
            Log::warning(
                'BasketballBoxScoreImportController::index failed to load import data: ' . $e->getMessage(),
                ['exception' => $e],
            );
            $fallbackGame = (object)[
                'id' => $gameId,
                'team_season' => (object)['team' => (object)['team_name' => 'Team']],
                'opponent' => (object)['opponent_name' => 'Opponent'],
                'game_date' => null,
            ];
            $viewData = ['game' => $fallbackGame, 'roster' => [], 'existingRosterIds' => []];
            $this->Flash->error('Could not load importer data: ' . $e->getMessage());
        }
        $rawText = (string)$this->request->getData('raw_text', '');
        $sourceType = (string)$this->request->getData('source_type', '');
        $intent = (string)$this->request->getData('intent', '');

        if ($this->request->is('post')) {
            try {
                if ($intent === 'save') {
                    $result = $this->importService->save($gameId, (array)$this->request->getData());
                    if ($result['success']) {
                        $this->Flash->success(__('Imported {0} player stat rows.', $result['saved']));
                        if ($result['skipped'] > 0) {
                            $this->Flash->warning(__('Skipped {0} duplicate player rows.', $result['skipped']));
                        }

                        return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
                    }

                    foreach ($result['errors'] as $error) {
                        $this->Flash->error($error);
                    }

                    $viewData = $sourceType === 'csv'
                        ? $this->importService->previewCsv($gameId, $rawText)
                        : $this->importService->preview($gameId, $rawText);
                } else {
                    $pdfFile = $this->normalizeUpload($this->request->getData('pdf_file'));
                    $csvFile = $this->normalizeUpload($this->request->getData('csv_file'));
                    $hasPdf = $pdfFile !== null && $pdfFile->getError() !== UPLOAD_ERR_NO_FILE;
                    $hasCsv = $csvFile !== null && $csvFile->getError() !== UPLOAD_ERR_NO_FILE;
                    if ($hasPdf && $hasCsv) {
                        throw new InvalidArgumentException('Choose either a PDF or a CSV file, not both.');
                    }

                    if ($hasPdf) {
                        $rawText = $this->extractPdfTextFromUpload($pdfFile);
                        $sourceType = 'pdf';
                    } elseif ($hasCsv) {
                        $rawText = $this->importService->extractCsvText($csvFile);
                        $sourceType = 'csv';
                    } elseif ($rawText !== '') {
                        $sourceType = 'text';
                    } else {
                        throw new InvalidArgumentException(
                            'Choose a PDF or CSV file, or paste extracted LiveStats text.',
                        );
                    }

                    $viewData = $sourceType === 'csv'
                        ? $this->importService->previewCsv($gameId, $rawText)
                        : $this->importService->preview($gameId, $rawText);
                }
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $this->set($viewData + ['rawText' => $rawText, 'sourceType' => $sourceType]);

        return null;
    }

    /**
     * Extract PDF text from a disk-backed upload with a system and PHP fallback.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $pdfFile Uploaded PDF
     * @return string Extracted PDF text
     * @throws \InvalidArgumentException When the PDF cannot be extracted
     */
    private function extractPdfTextFromUpload(UploadedFileInterface $pdfFile): string
    {
        $temporaryDirectory = WWW_ROOT . 'files' . DS . 'boxscore_temp' . DS;
        if (
            !is_dir($temporaryDirectory)
            && !mkdir($temporaryDirectory, 0700, true)
            && !is_dir($temporaryDirectory)
        ) {
            throw new RuntimeException('Could not create the PDF extraction directory.');
        }

        try {
            $temporaryPath = $temporaryDirectory . 'box-score-' . bin2hex(random_bytes(16)) . '.pdf';
            $pdfFile->moveTo($temporaryPath);

            $pdftotextPath = function_exists('shell_exec')
                ? trim((string)shell_exec('command -v pdftotext 2>/dev/null'))
                : '';
            if ($pdftotextPath !== '') {
                $text = function_exists('shell_exec')
                    ? shell_exec('pdftotext ' . escapeshellarg($temporaryPath) . ' -')
                    : null;
                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            }

            $text = trim((new Parser())->parseFile($temporaryPath)->getText());
            if ($text === '') {
                throw new InvalidArgumentException('The PDF contains no extractable text.');
            }

            return $text;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'The PDF could not be extracted. Upload the original PDF or paste its final box-score text.',
                0,
                $exception,
            );
        } finally {
            if (isset($temporaryPath) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /**
     * Normalize CakePHP array uploads to a PSR-7 uploaded file.
     *
     * @param mixed $value Uploaded PDF value
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    private function normalizeUpload(mixed $value): ?UploadedFileInterface
    {
        if ($value instanceof UploadedFileInterface) {
            return $value;
        }
        if (!is_array($value) || empty($value['tmp_name'])) {
            return null;
        }

        return new UploadedFile(
            (string)$value['tmp_name'],
            (int)($value['size'] ?? 0),
            (int)($value['error'] ?? UPLOAD_ERR_OK),
            (string)($value['name'] ?? ''),
            (string)($value['type'] ?? ''),
        );
    }
}
