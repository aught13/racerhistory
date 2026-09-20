<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballBoxScoreImportService;
use Cake\Http\Response;
use Cake\Log\Log;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

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
        $this->FormProtection->unlockFields([
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
        } catch (\Throwable $e) {
            // Unexpected errors during bootstrap of importer data should not
            // cause a 500 response in the admin UI test matrix. Provide a
            // minimal fallback so the importer page can render and show an
            // error message instead of failing the request.
            Log::warning('BasketballBoxScoreImportController::index failed to load import data: ' . $e->getMessage(), ['exception' => $e]);
            $fallbackGame = (object) [
                'id' => $gameId,
                'team_season' => (object) ['team' => (object) ['team_name' => 'Team']],
                'opponent' => (object) ['opponent_name' => 'Opponent'],
                'game_date' => null,
            ];
            $viewData = ['game' => $fallbackGame, 'roster' => [], 'existingRosterIds' => []];
            $this->Flash->error('Could not load importer data: ' . $e->getMessage());
        }
        $rawText = (string)$this->request->getData('raw_text', '');
        $intent = (string)$this->request->getData('intent', '');

        if ($this->request->is('post')) {
            try {
                $pdfFile = $this->normalizePdfUpload($this->request->getData('pdf_file'));
                if ($pdfFile !== null && $pdfFile->getError() !== UPLOAD_ERR_NO_FILE) {
                    $rawText = $this->importService->extractPdfText($pdfFile);
                }
                if ($rawText === '') {
                    throw new InvalidArgumentException('Choose a PDF file or paste extracted LiveStats text.');
                }

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
                }

                $viewData = $this->importService->preview($gameId, $rawText);
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $this->set($viewData + ['rawText' => $rawText]);

        return null;
    }

    /**
     * Normalize CakePHP array uploads to a PSR-7 uploaded file.
     *
     * @param mixed $value Uploaded PDF value
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    private function normalizePdfUpload(mixed $value): ?UploadedFileInterface
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
