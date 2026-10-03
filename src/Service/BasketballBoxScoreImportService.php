<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Entity\Person;
use Cake\Http\Client;
use Cake\ORM\Locator\LocatorAwareTrait;
use finfo;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Prepares and commits NCAA LiveStats basketball box-score imports.
 */
class BasketballBoxScoreImportService
{
    use LocatorAwareTrait;

    private const MAX_PDF_BYTES = 20971520;

    private OfficialBasketballBoxScoreParser $parser;

    private BasketballBoxScoreCsvParser $csvParser;

    private BasketballStatsAdminService $statsAdminService;

    private GameService $gameService;

    /**
     * @param \App\Service\OfficialBasketballBoxScoreParser|null $parser Parser dependency
     * @param \App\Service\BasketballStatsAdminService|null $statsAdminService Existing stat writer
     * @param \App\Service\BasketballBoxScoreCsvParser|null $csvParser CSV fallback parser
     * @param \App\Service\GameService|null $gameService Game results writer
     */
    public function __construct(
        ?OfficialBasketballBoxScoreParser $parser = null,
        ?BasketballStatsAdminService $statsAdminService = null,
        ?BasketballBoxScoreCsvParser $csvParser = null,
        ?GameService $gameService = null,
    ) {
        $this->parser = $parser ?? new OfficialBasketballBoxScoreParser();
        $this->statsAdminService = $statsAdminService ?? new BasketballStatsAdminService();
        $this->csvParser = $csvParser ?? new BasketballBoxScoreCsvParser();
        $this->gameService = $gameService ?? new GameService();
    }

    /**
     * Load the game and roster choices used by the importer screen.
     *
     * @param int $gameId Game ID
     * @return array{game: \App\Model\Entity\Game, roster: list<array{id: int, jersey: string, name: string, label: string}>, existingRosterIds: list<int>}
     */
    public function getAdminImportData(int $gameId): array
    {
        $game = $this->getGame($gameId);
        /** @var \App\Model\Table\TeamSeasonRostersTable $rosterTable */
        $rosterTable = $this->fetchTable('TeamSeasonRosters');
        $roster = [];

        $rows = $rosterTable->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => (int)$game->team_season_id])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();
        foreach ($rows as $row) {
            $person = $row->get('person');
            $name = $person instanceof Person
                ? (string)($person->display ?? $person->full ?? '')
                : '';
            $jersey = (string)($row->get('roster_number') ?? '');
            $roster[] = [
                'id' => (int)$row->get('id'),
                'jersey' => $jersey,
                'name' => $name,
                'label' => trim(($jersey !== '' ? '#' . $jersey . ' ' : '') . $name),
            ];
        }

        /** @var \App\Model\Table\StatBasketGamePersonTable $statTable */
        $statTable = $this->fetchTable('StatBasketGamePerson');
        $existingRosterIds = [];
        foreach ($statTable->find()->where(['game_id' => $gameId])->all() as $stat) {
            $existingRosterIds[] = (int)$stat->get('team_season_roster_id');
        }

        return compact('game', 'roster', 'existingRosterIds');
    }

    /**
     * Extract text from an uploaded LiveStats PDF.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded PDF
     * @return string Extracted PDF text
     * @throws \InvalidArgumentException When the upload is invalid or unreadable
     */
    public function extractPdfText(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The PDF upload did not complete successfully.');
        }

        $size = $file->getSize();
        if ($size !== null && $size > self::MAX_PDF_BYTES) {
            throw new InvalidArgumentException('The PDF is too large. Please upload a file smaller than 20 MB.');
        }
        $filename = strtolower((string)$file->getClientFilename());
        $mediaType = strtolower((string)$file->getClientMediaType());
        if (!str_ends_with($filename, '.pdf') && $mediaType !== 'application/pdf') {
            throw new InvalidArgumentException('Please upload a PDF file.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'rh-box-score-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Could not create a temporary file for PDF extraction.');
        }

        try {
            $this->copyUploadedFile($file, $temporaryPath);
            $detectedType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            if ($detectedType !== 'application/pdf') {
                throw new InvalidArgumentException('The uploaded file is not a valid PDF.');
            }

            $layoutText = $this->runPdfTextExtractor($temporaryPath, true);
            $rawText = '';
            try {
                $this->parser->parse($layoutText);

                return $layoutText;
            } catch (InvalidArgumentException $layoutException) {
                try {
                    $rawText = $this->runPdfTextExtractor($temporaryPath, false);
                    $this->parser->parse($rawText);

                    return $rawText;
                } catch (InvalidArgumentException $rawException) {
                    if (substr_count($layoutText, 'Totals') < 2 && substr_count($rawText, 'Totals') < 2) {
                        return $layoutText;
                    }

                    throw $rawException;
                }
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /**
     * Read a structured CSV upload without storing it permanently.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded CSV
     * @return string CSV contents
     * @throws \InvalidArgumentException When the upload is invalid or unreadable
     */
    public function extractCsvText(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The CSV upload did not complete successfully.');
        }

        $size = $file->getSize();
        if ($size !== null && $size > self::MAX_PDF_BYTES) {
            throw new InvalidArgumentException('The CSV file is too large. Please upload a file smaller than 20 MB.');
        }

        $filename = strtolower((string)$file->getClientFilename());
        $mediaType = strtolower((string)$file->getClientMediaType());
        $allowedMediaTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (!str_ends_with($filename, '.csv') && !in_array($mediaType, $allowedMediaTypes, true)) {
            throw new InvalidArgumentException('Please upload a CSV file.');
        }

        try {
            $stream = $file->getStream();
            $stream->rewind();
            $contents = $stream->getContents();
        } catch (Throwable) {
            throw new InvalidArgumentException('The CSV file could not be read.');
        }
        if ($contents === '') {
            throw new InvalidArgumentException('The CSV file is empty.');
        }

        return $contents;
    }

    /**
     * Return the editable CSV fallback template.
     *
     * @return string CSV template contents
     */
    public function getCsvTemplate(): string
    {
        return $this->csvParser->template();
    }

    /**
     * Fetch an athletics HTML box score and extract its visible text.
     *
     * @param string $url Public goracers.com box-score URL
     * @return string Extracted HTML text
     * @throws \InvalidArgumentException When the URL or response is invalid
     */
    public function extractHtmlText(string $url): string
    {
        $parts = parse_url(trim($url));
        $host = strtolower((string)($parts['host'] ?? ''));
        if (
            !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
            || ($host !== 'goracers.com' && !str_ends_with($host, '.goracers.com'))
        ) {
            throw new InvalidArgumentException('Enter a valid goracers.com box-score URL.');
        }

        try {
            $response = (new Client(['timeout' => 20]))->get($url);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new InvalidArgumentException('The box-score page could not be loaded.');
            }
            $crawler = new Crawler($response->getStringBody(), $url);
            $text = $this->serializeHtmlBoxScoreTables($crawler);
            if ($text === '') {
                $text = trim($crawler->filterXPath('//body')->text(null, true));
            }
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('The box-score page could not be read.', 0, $exception);
        }
        if ($text === '') {
            throw new InvalidArgumentException('The box-score page did not contain readable text.');
        }

        return $text;
    }

    /**
     * Serialize final HTML box-score tables without flattening their cells.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $crawler Parsed page
     * @return string Structured text for the box-score parser
     */
    private function serializeHtmlBoxScoreTables(Crawler $crawler): string
    {
        $tables = $crawler->filterXPath('//table');
        if ($tables->count() < 4) {
            $preformatted = $crawler->filterXPath('//pre');
            $sections = $preformatted->each(static fn(Crawler $section): string => trim($section->text(null, false)));
            $sections = array_values(array_filter($sections, static fn(string $section): bool => $section !== ''));

            return implode("\n", $sections);
        }

        $scoreRows = $this->extractHtmlTableRows($tables->eq(0));
        if (count($scoreRows) < 3 || count($scoreRows[1]) < 4 || count($scoreRows[2]) < 4) {
            return '';
        }

        $text = "Game Information\n";
        $periodHeaders = array_slice($scoreRows[0], 1);
        $text .= 'PERIOD_SCORE_HEADERS|' . implode('|', $periodHeaders) . "\n";
        foreach ([1, 3] as $teamIndex => $tableIndex) {
            $scoreRow = $scoreRows[$teamIndex + 1];
            $text .= 'PERIOD_SCORE|' . $scoreRow[0] . '|' . implode('|', array_slice($scoreRow, 1)) . "\n";
            $finalScore = $scoreRow[count($scoreRow) - 1];
            $text .= $scoreRow[0] . ' - ' . $finalScore . "\n";
            foreach ($this->extractHtmlTableRows($tables->eq($tableIndex)) as $row) {
                foreach ($row as $cell) {
                    $text .= $cell . "\n";
                }
            }

            $summaryTableIndex = $tableIndex + 1;
            if ($tables->count() <= $summaryTableIndex) {
                continue;
            }
            foreach ($this->extractHtmlTableRows($tables->eq($summaryTableIndex)) as $summaryRow) {
                if (!isset($summaryRow[0]) || preg_match('/^(?:\d+(?:st|nd|rd|th)|OT\b)/i', $summaryRow[0]) !== 1) {
                    continue;
                }
                $text .= 'PERIOD_BOX|' . $teamIndex . '|' . $summaryRow[0] . '|'
                    . ($summaryRow[1] ?? '') . '|' . ($summaryRow[2] ?? '') . '|'
                    . ($summaryRow[3] ?? '') . "\n";
            }
        }
        $bodyText = trim($crawler->filterXPath('//body')->text(null, true));
        if ($bodyText !== '') {
            if ($tables->count() > 5) {
                $detailRows = $this->extractHtmlTableRows($tables->eq(5));
                $periodHeaders = array_slice($detailRows[0] ?? [], 1);
                $fieldLabels = [
                    'points off turnovers' => 'OTO',
                    'second chance points' => 'SND',
                    'bench points' => 'BN',
                    'lead gained by' => 'LC',
                    'lead changed by' => 'LC',
                    'points in the paint' => 'PNT',
                    'fast break points' => 'FB',
                    'scores tied by' => 'TIED',
                    'field goals' => 'FG',
                    'free throws' => 'FT',
                    '3-points' => '3PT',
                    'offensive rebounds' => 'ORB',
                    'total rebounds' => 'RB',
                    'blocks' => 'BS',
                    'steals' => 'STL',
                    'assists' => 'AST',
                    'turnovers' => 'TRN',
                    'personal fouls' => 'PF',
                ];
                $currentField = null;
                $processedSides = 0;
                foreach (array_slice($detailRows, 1) as $detailRow) {
                    $rowLabel = strtolower(trim($detailRow[0] ?? ''));
                    if (isset($fieldLabels[$rowLabel])) {
                        $currentField = $fieldLabels[$rowLabel];
                        $processedSides = 0;
                        continue;
                    }
                    if ($currentField === null) {
                        continue;
                    }

                    $sideIndex = array_search(trim($detailRow[0] ?? ''), [$scoreRows[1][0], $scoreRows[2][0]], true);
                    if (!is_int($sideIndex)) {
                        continue;
                    }

                    $periodValues = [];
                    $finalValue = null;
                    foreach ($periodHeaders as $index => $header) {
                        $value = trim($detailRow[$index + 1] ?? '');
                        if ($value === '') {
                            continue;
                        }
                        if (in_array(strtolower(trim($header)), ['t', 'total', 'final'], true)) {
                            $finalValue = $value;
                            continue;
                        }

                        $periodCode = null;
                        if (preg_match('/^\d+$/', trim($header)) === 1) {
                            $periodCode = (string)(int)$header;
                        } elseif (preg_match('/^OT\s*(\d*)$/i', trim($header), $overtimeMatch) === 1) {
                            $overtimeNumber = (int)$overtimeMatch[1];
                            $periodCode = $overtimeNumber > 1 ? 'OT' . $overtimeNumber : 'OT';
                        }
                        if ($periodCode !== null) {
                            $periodValues[] = $periodCode . '=' . $value;
                        }
                    }
                    if ($periodValues !== []) {
                        $text .= 'PERIOD_DETAIL|' . $sideIndex . '|' . $currentField . '|'
                            . implode('|', $periodValues) . "\n";
                    }
                    if ($finalValue !== null) {
                        $text .= 'FINAL_DETAIL|' . $sideIndex . '|' . $currentField . '|' . $finalValue . "\n";
                    }
                    $processedSides++;
                    if ($processedSides >= 2) {
                        $currentField = null;
                    }
                }
            }
            $text .= "\n" . $bodyText;
        }

        return $text;
    }

    /**
     * Extract table cells as row-oriented strings.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $table Table node
     * @return list<list<string>> Table rows and cells
     */
    private function extractHtmlTableRows(Crawler $table): array
    {
        return $table->filterXPath('.//tr')->each(
            static fn(Crawler $row): array => $row->filterXPath('.//th|.//td')->each(
                static fn(Crawler $cell): string => trim(str_replace(
                    "\xC2\xA0",
                    ' ',
                    $cell->text(null, true),
                )),
            ),
        );
    }

    /**
     * Parse text and prepare editable rows for the importer screen.
     *
     * @param int $gameId Game ID
     * @param string $text Extracted PDF text
     * @return array<string, mixed>
     */
    public function preview(int $gameId, string $text): array
    {
        return $this->buildPreview($gameId, $this->parser->parse($text), $text);
    }

    /**
     * Parse structured CSV and prepare editable rows for the importer screen.
     *
     * @param int $gameId Game ID
     * @param string $contents CSV contents
     * @return array<string,mixed>
     */
    public function previewCsv(int $gameId, string $contents): array
    {
        return $this->buildPreview($gameId, $this->csvParser->parse($contents), $contents);
    }

    /**
     * Add roster mapping and application context to parsed box-score data.
     *
     * @param int $gameId Game ID
     * @param array{date:string|null,teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>,game_results?:array{attendance:string|null,officials:array<int|string,string>,period_scores:array<string,array<string,int>>},period_boxes?:array<string,array<string,array<string,int|null>>>} $parsed Parsed box score
     * @param string $rawText Source PDF text, when available
     * @return array<string,mixed>
     */
    private function buildPreview(int $gameId, array $parsed, string $rawText): array
    {
        $viewData = $this->getAdminImportData($gameId);
        $teamIndex = $this->resolveTeamIndex($parsed['teams'], $viewData['game']);
        $warnings = [];

        if ($teamIndex === null) {
            $teamIndex = 0;
            $warnings[] = 'The PDF teams could not be matched to this game. '
                . 'Verify the team and opponent columns before saving.';
        }

        $team = $parsed['teams'][$teamIndex];
        $opponent = $parsed['teams'][1 - $teamIndex];
        $teamRows = [];
        foreach ($team['players'] as $player) {
            $match = $this->matchRoster($player, $viewData['roster']);
            if ($match === null) {
                $warnings[] = sprintf('No roster match was found for #%s %s.', $player['jersey'], $player['name']);
            }
            if ($match !== null && in_array($match['id'], $viewData['existingRosterIds'], true)) {
                $warnings[] = sprintf('%s already has game stats and will be skipped.', $match['label']);
            }
            $teamRows[] = $player + [
                'team_season_roster_id' => $match['id'] ?? '',
                'matched_name' => $match['label'] ?? '',
                'match_type' => $match['type'] ?? 'none',
            ];
        }

        $opponentRows = array_map(
            static fn(array $player): array => $player + ['period' => 'Z', 'GP' => '1'],
            $opponent['players'],
        );
        $regularPeriodCount = max(1, (int)($viewData['game']->periods ?? 2));
        $sourceGameResults = $parsed['game_results'] ?? [
            'attendance' => null,
            'officials' => [],
            'period_scores' => [],
        ];
        $gameResults = ['attendance' => $sourceGameResults['attendance']];
        foreach ($sourceGameResults['officials'] as $index => $official) {
            $key = is_string($index) && preg_match('/^official_\d+$/', $index) === 1
                ? $index
                : 'official_' . ((int)$index + 1);
            $gameResults[$key] = $official;
        }
        foreach ($sourceGameResults['period_scores'] as $period => $scores) {
            $periodPrefix = $this->gameResultPeriodPrefix((string)$period, $regularPeriodCount);
            if ($periodPrefix === null) {
                continue;
            }
            $sourceTeamSide = $teamIndex === 0 ? 'team' : 'opponent';
            $sourceOpponentSide = $teamIndex === 0 ? 'opponent' : 'team';
            if (array_key_exists($sourceTeamSide, $scores)) {
                $gameResults[$periodPrefix . '_team'] = (string)$scores[$sourceTeamSide];
            }
            if (array_key_exists($sourceOpponentSide, $scores)) {
                $gameResults[$periodPrefix . '_opponent'] = (string)$scores[$sourceOpponentSide];
            }
        }

        $periodBoxes = [];
        foreach ($parsed['period_boxes'] ?? [] as $period => $sides) {
            $periodCode = $this->mapSourcePeriod((string)$period, $regularPeriodCount);
            $sourceTeamSide = $teamIndex === 0 ? 'team' : 'opponent';
            $sourceOpponentSide = $teamIndex === 0 ? 'opponent' : 'team';
            if (!empty($sides[$sourceTeamSide])) {
                $periodBoxes['team_' . $periodCode] = $this->derivePeriodDefensiveRebounds(
                    $sides[$sourceTeamSide],
                );
            }
            if (!empty($sides[$sourceOpponentSide])) {
                $periodBoxes['opponent_' . $periodCode] = $this->derivePeriodDefensiveRebounds(
                    $sides[$sourceOpponentSide],
                );
            }
        }

        return $viewData + [
            'rawText' => $rawText,
            'parsed' => $parsed,
            'team' => $team,
            'opponent' => $opponent,
            'teamRows' => $teamRows,
            'opponentRows' => $opponentRows,
            'teamBox' => $team['totals'],
            'opponentBox' => $opponent['totals'],
            'gameResults' => $gameResults,
            'periodBoxes' => $periodBoxes,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Save approved final player rows and team totals.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $data Import form data
     * @return array{success: bool, saved: int, skipped: int, errors: list<string>}
     */
    public function save(int $gameId, array $data): array
    {
        $teamRows = $this->normalizeRows($data['team_rows'] ?? []);
        $opponentRows = $this->normalizeRows($data['opponent_rows'] ?? []);
        $errors = [];
        $unmatched = array_filter(
            $teamRows,
            static fn(array $row): bool => (int)($row['team_season_roster_id'] ?? 0) < 1,
        );
        if ($unmatched !== []) {
            $errors[] = 'Every team player row must be mapped to a roster player before saving.';
        }

        if ($errors !== []) {
            return ['success' => false, 'saved' => 0, 'skipped' => 0, 'errors' => $errors];
        }

        $addToTotals = !empty($data['add_to_totals']);
        $personResult = ['saved' => 0, 'skipped' => 0, 'errors' => []];
        if ($teamRows !== []) {
            $personResult = $this->statsAdminService->saveAdminGamePersonRows($gameId, $teamRows, $addToTotals);
        }
        $opponentResult = ['saved' => 0, 'skipped' => 0, 'errors' => []];
        if ($opponentRows !== []) {
            $opponentResult = $this->statsAdminService->saveAdminGameOpponentRows($gameId, $opponentRows);
        }

        $teamBox = $this->normalizeBox(
            (array)($data['team_box'] ?? []),
            array_keys((array)($data['team_box_selected'] ?? [])),
        );
        $opponentBox = $this->normalizeBox(
            (array)($data['opponent_box'] ?? []),
            array_keys((array)($data['opponent_box_selected'] ?? [])),
        );
        if ($teamBox !== [] || $opponentBox !== []) {
            $boxResult = $this->statsAdminService->saveAdminGameBox($gameId, [
                'team' => $teamBox,
                'opponent' => $opponentBox,
                'add_to_totals' => $addToTotals,
                'team_minutes' => $data['team_minutes'] ?? 0,
            ]);
            if (!$boxResult['success']) {
                $errors[] = 'The team box score could not be saved.';
            }
        }

        $periodBoxes = $this->normalizePeriodBoxes(
            $data['period_boxes'] ?? [],
            $data['period_boxes_selected'] ?? [],
        );
        if ($periodBoxes !== []) {
            $periodResult = $this->statsAdminService->saveAdminGameBoxPeriods($gameId, $periodBoxes);
            if (!$periodResult['success']) {
                $errors = array_merge($errors, $periodResult['errors']);
            }
        }

        $gameResults = $this->normalizeGameResults(
            $data['game_results'] ?? [],
            $data['game_results_selected'] ?? [],
        );
        $errors = array_merge($errors, $this->saveGameResults($gameId, $gameResults));

        $errors = array_merge($errors, $personResult['errors'], $opponentResult['errors']);

        return [
            'success' => $errors === [],
            'saved' => $personResult['saved'] + $opponentResult['saved'],
            'skipped' => $personResult['skipped'] + $opponentResult['skipped'],
            'errors' => $errors,
        ];
    }

    /**
     * @param mixed $rows Posted rows
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalizedRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (empty($row['import'])) {
                continue;
            }
            unset($row['import']);
            if (isset($row['MIN'])) {
                $row['MIN'] = $this->normalizeMinutes($row['MIN']);
            }
            $normalizedRows[] = $row;
        }

        return $normalizedRows;
    }

    /**
     * Convert LiveStats MM:SS minutes to a numeric minute value.
     *
     * @param mixed $minutes Posted minutes value
     * @return mixed Numeric minutes or the original value
     */
    private function normalizeMinutes(mixed $minutes): mixed
    {
        if (!is_string($minutes) || !preg_match('/^(\d{1,3}):(\d{2})$/', $minutes, $matches)) {
            return $minutes;
        }

        $decimalMinutes = (int)$matches[1] + ((int)$matches[2] / 60);

        return number_format($decimalMinutes, 2, '.', '');
    }

    /**
     * Copy the PSR-7 upload to a private temporary path.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file
     * @param string $temporaryPath Destination path
     * @return void
     */
    private function copyUploadedFile(UploadedFileInterface $file, string $temporaryPath): void
    {
        $source = $file->getStream();
        $destination = fopen($temporaryPath, 'wb');
        if ($destination === false) {
            throw new RuntimeException('Could not write the temporary PDF file.');
        }

        try {
            while (!$source->eof()) {
                $chunk = $source->read(8192);
                if ($chunk === '') {
                    break;
                }
                if (fwrite($destination, $chunk) === false) {
                    throw new RuntimeException('Could not write the temporary PDF file.');
                }
            }
        } finally {
            fclose($destination);
        }
    }

    /**
     * Run pdftotext without exposing the uploaded path to a shell expression.
     *
     * @param string $temporaryPath Temporary PDF path
     * @param bool $layout Preserve PDF layout when extracting text
     * @return string Extracted text
     */
    private function runPdfTextExtractor(string $temporaryPath, bool $layout = true): string
    {
        $layoutOption = $layout ? '-layout ' : '';
        $command = 'pdftotext ' . $layoutOption . escapeshellarg($temporaryPath) . ' -';
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return $this->extractTextFromPdfBytes($temporaryPath);
        }

        $output = stream_get_contents($pipes[1]) ?: '';
        $errorOutput = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            if ($exitCode === 127 || str_contains($errorOutput, 'pdftotext: not found')) {
                return $this->extractTextFromPdfBytes($temporaryPath);
            }

            throw new InvalidArgumentException(
                'The PDF could not be read. ' . trim($errorOutput),
            );
        }
        if (trim($output) === '') {
            throw new InvalidArgumentException('The PDF contains no extractable text.');
        }

        return $output;
    }

    /**
     * Extract readable text from a PDF without external binaries.
     *
     * This fallback handles the simple text-only PDFs used in tests and any
     * uncompressed PDF streams that store text in literal string operators.
     *
     * @param string $temporaryPath Temporary PDF path
     * @return string Extracted text
     */
    private function extractTextFromPdfBytes(string $temporaryPath): string
    {
        $contents = file_get_contents($temporaryPath);
        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException('The PDF could not be read.');
        }

        try {
            $text = trim((new PdfParser())->parseFile($temporaryPath)->getText());
            if ($text !== '') {
                return $text;
            }
        } catch (Throwable) {
            // Fall through to the small built-in parser for simple PDF streams.
        }

        $textChunks = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $contents, $streamMatches)) {
            foreach ($streamMatches[1] as $stream) {
                if (!preg_match_all('/\((?:\\.|[^\\()])*\)/s', $stream, $stringMatches)) {
                    continue;
                }

                $line = [];
                foreach ($stringMatches[0] as $literalString) {
                    $line[] = $this->decodePdfLiteralString($literalString);
                }

                $textChunks[] = implode(' ', $line);
            }
        }

        $text = trim(implode("\n", $textChunks));
        if ($text === '' || preg_match('/[\p{L}\p{N}]{3}/u', $text) !== 1) {
            throw new InvalidArgumentException('The PDF contains no extractable text.');
        }

        return $text;
    }

    /**
     * Decode a PDF literal string token into plain text.
     *
     * @param string $literalString PDF literal string, including parentheses
     * @return string Decoded text
     */
    private function decodePdfLiteralString(string $literalString): string
    {
        $literalString = substr($literalString, 1, -1);
        $decoded = '';
        $length = strlen($literalString);

        for ($index = 0; $index < $length; $index++) {
            $char = $literalString[$index];
            if ($char !== '\\') {
                $decoded .= $char;

                continue;
            }

            $index++;
            if ($index >= $length) {
                break;
            }

            $escaped = $literalString[$index];
            if (ctype_digit($escaped)) {
                $octal = $escaped;
                $octalLength = 1;
                while (
                    $index + 1 < $length
                    && $octalLength < 3
                    && ctype_digit($literalString[$index + 1])
                ) {
                    $index++;
                    $octal .= $literalString[$index];
                    $octalLength++;
                }

                $decoded .= chr(octdec($octal));
                continue;
            }

            $decoded .= match ($escaped) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\x08",
                'f' => "\f",
                '(' => '(',
                ')' => ')',
                '\\' => '\\',
                default => $escaped,
            };
        }

        return $decoded;
    }

    /**
     * Keep only fields accepted by the basketball box-score table.
     *
     * @param array<string, mixed> $box Posted box data
     * @param list<string> $selectedFields Fields selected for import
     * @return array<string, mixed>
     */
    private function normalizeBox(array $box, array $selectedFields): array
    {
        $fields = [
            'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
            'AST', 'STL', 'BS', 'TRN', 'PF', 'TF', 'PTS', 'PNT', 'OTO',
            'SND', 'FB', 'BN', 'TIED', 'LC',
        ];
        $normalized = [];
        foreach ($fields as $field) {
            if (in_array($field, $selectedFields, true) && array_key_exists($field, $box) && $box[$field] !== '') {
                $normalized[$field] = $box[$field];
            }
        }

        return $normalized;
    }

    /**
     * Keep only selected fields from each period and side.
     *
     * @param mixed $boxes Posted period box values
     * @param mixed $selected Posted selected field names by side/period
     * @return array<string,array<string,mixed>> Selected period rows
     */
    private function normalizePeriodBoxes(mixed $boxes, mixed $selected): array
    {
        if (!is_array($boxes) || !is_array($selected)) {
            return [];
        }

        $normalized = [];
        foreach ($boxes as $key => $box) {
            if (!is_array($box) || !isset($selected[$key]) || !is_array($selected[$key])) {
                continue;
            }
            $fields = array_keys(array_filter($selected[$key], static fn(mixed $value): bool => !empty($value)));
            $periodBox = $this->normalizeBox($box, $fields);
            if ($periodBox !== []) {
                $normalized[(string)$key] = $periodBox;
            }
        }

        return $normalized;
    }

    /**
     * Keep only selected attendance, period score, and official fields.
     *
     * @param mixed $results Posted game result values
     * @param mixed $selected Posted selected game result keys
     * @return array<string,string> Selected non-empty values
     */
    private function normalizeGameResults(mixed $results, mixed $selected): array
    {
        if (!is_array($results) || !is_array($selected)) {
            return [];
        }

        $normalized = [];
        foreach ($selected as $key => $import) {
            $key = (string)$key;
            if (empty($import) || !array_key_exists($key, $results) || !is_scalar($results[$key])) {
                continue;
            }
            $isSupportedField = preg_match(
                '/^(?:period_\d+_(?:team|opponent)|overtime_\d+_(?:team|opponent)|official_\d+)$/',
                $key,
            ) === 1;
            if ($key !== 'attendance' && !$isSupportedField) {
                continue;
            }
            $value = trim((string)$results[$key]);
            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Save the selected game attendance field and sport-specific EAV values.
     *
     * @param int $gameId Game ID
     * @param array<string,string> $results Selected values
     * @return list<string> Save errors
     */
    private function saveGameResults(int $gameId, array $results): array
    {
        $errors = [];
        if (array_key_exists('attendance', $results)) {
            /** @var \App\Model\Table\GamesTable $gameTable */
            $gameTable = $this->fetchTable('Games');
            $game = $gameTable->get($gameId);
            $game->attendance = $results['attendance'];
            if (!$gameTable->save($game)) {
                $errors[] = 'Game attendance could not be saved.';
            }
            unset($results['attendance']);
        }

        if ($results !== []) {
            try {
                $this->gameService->saveGameEavFromRequest($gameId, $results);
            } catch (Throwable) {
                $errors[] = 'Game period scores or officials could not be saved.';
            }
        }

        return $errors;
    }

    /**
     * Convert a parsed period to the game's EAV field prefix.
     *
     * @param string $period Parsed period identifier
     * @param int $regularPeriodCount Number of regulation periods
     * @return string|null EAV field prefix
     */
    private function gameResultPeriodPrefix(string $period, int $regularPeriodCount): ?string
    {
        $period = $this->mapSourcePeriod($period, $regularPeriodCount);
        if (preg_match('/^\d+$/', $period) === 1) {
            return 'period_' . $period;
        }
        if (preg_match('/^OT(\d*)$/i', $period, $matches) === 1) {
            return 'overtime_' . max(1, (int)$matches[1]);
        }

        return null;
    }

    /**
     * Convert source period numbers after regulation into overtime codes.
     *
     * @param string $period Source period identifier
     * @param int $regularPeriodCount Number of regulation periods configured for the game
     * @return string Database period code
     */
    private function mapSourcePeriod(string $period, int $regularPeriodCount): string
    {
        if (preg_match('/^(\d+)$/', $period, $matches) !== 1) {
            return $period;
        }

        $periodNumber = (int)$matches[1];
        if ($periodNumber <= $regularPeriodCount) {
            return $period;
        }

        $overtimeNumber = $periodNumber - $regularPeriodCount;

        return $overtimeNumber > 1 ? 'OT' . $overtimeNumber : 'OT';
    }

    /**
     * Derive defensive rebounds when a period only supplies offensive and total rebounds.
     *
     * @param array<string,int|null> $stats Parsed period stats
     * @return array<string,int|null> Period stats including derived defensive rebounds
     */
    private function derivePeriodDefensiveRebounds(array $stats): array
    {
        if (isset($stats['DRB']) && is_numeric($stats['DRB'])) {
            return $stats;
        }
        if (!is_numeric($stats['RB'] ?? null) || !is_numeric($stats['ORB'] ?? null)) {
            return $stats;
        }

        $totalRebounds = (int)$stats['RB'];
        $offensiveRebounds = (int)$stats['ORB'];
        if ($totalRebounds >= $offensiveRebounds) {
            $stats['DRB'] = $totalRebounds - $offensiveRebounds;
        }

        return $stats;
    }

    /**
     * @param list<array<string, mixed>> $teams Parsed teams
     * @param \App\Model\Entity\Game $game Game entity
     * @return int|null Parsed team index
     */
    private function resolveTeamIndex(array $teams, Game $game): ?int
    {
        $teamName = (string)($game->team_season->team->team_name ?? '');
        $teamNames = array_filter([
            $teamName,
            (string)($game->team_season->team->team_nickname ?? ''),
            (string)($game->team_season->team->team_scorebug ?? ''),
        ]);
        $opponentNames = array_filter([
            (string)($game->opponent->opponent_name ?? ''),
            (string)($game->opponent->opponent_short ?? ''),
            (string)($game->opponent->opponent_abbr ?? ''),
        ]);
        foreach ($teams as $index => $parsedTeam) {
            $label = $this->normalizeName((string)$parsedTeam['label']);
            foreach ($teamNames as $candidate) {
                if ($this->namesMatch($label, $this->normalizeName($candidate))) {
                    return $index;
                }
            }
            foreach ($opponentNames as $candidate) {
                if ($this->namesMatch($label, $this->normalizeName($candidate))) {
                    return 1 - $index;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $player Parsed player
     * @param list<array{id: int, jersey: string, name: string, label: string}> $roster Roster choices
     * @return array{id: int, label: string, type: string}|null
     */
    private function matchRoster(array $player, array $roster): ?array
    {
        $jerseyMatches = array_values(array_filter(
            $roster,
            fn(array $row): bool => $this->normalizeJersey((string)$row['jersey'])
                === $this->normalizeJersey((string)$player['jersey']),
        ));
        if (count($jerseyMatches) === 1) {
            return [
                'id' => $jerseyMatches[0]['id'],
                'label' => $jerseyMatches[0]['label'],
                'type' => 'jersey',
            ];
        }

        $name = $this->normalizeName((string)$player['name']);
        $nameMatches = array_values(array_filter(
            $roster,
            fn(array $row): bool => $name !== '' && $this->normalizeName($row['name']) === $name,
        ));
        if (count($nameMatches) !== 1) {
            return null;
        }

        return [
            'id' => $nameMatches[0]['id'],
            'label' => $nameMatches[0]['label'],
            'type' => 'name',
        ];
    }

    /**
     * Normalize numeric jersey numbers so leading zeroes do not block a match.
     *
     * @param string $jersey Jersey value
     * @return string Normalized jersey
     */
    private function normalizeJersey(string $jersey): string
    {
        $jersey = trim($jersey);
        if ($jersey !== '' && ctype_digit($jersey)) {
            return ltrim($jersey, '0') ?: '0';
        }

        return strtolower($jersey);
    }

    /**
     * Normalize a team or player name for matching.
     *
     * @param string $name Source name
     * @return string Alphanumeric normalized name
     */
    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(['state', 'university'], ['st', ''], $name);

        return preg_replace('/[^a-z0-9]/', '', $name) ?? '';
    }

    /**
     * Determine whether two normalized names identify the same entity.
     *
     * @param string $left First normalized name
     * @param string $right Second normalized name
     * @return bool Whether the names match
     */
    private function namesMatch(string $left, string $right): bool
    {
        return $left !== '' && $right !== '' && (
            $left === $right || str_contains($left, $right) || str_contains($right, $left)
        );
    }

    /**
     * Load the game and associations used by the importer.
     *
     * @param int $gameId Game ID
     * @return \App\Model\Entity\Game Game entity
     */
    private function getGame(int $gameId): Game
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        return $game;
    }
}
