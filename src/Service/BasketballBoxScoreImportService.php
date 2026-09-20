<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Entity\Person;
use Cake\ORM\Locator\LocatorAwareTrait;
use finfo;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Prepares and commits NCAA LiveStats basketball box-score imports.
 */
class BasketballBoxScoreImportService
{
    use LocatorAwareTrait;

    private const MAX_PDF_BYTES = 20971520;

    private OfficialBasketballBoxScoreParser $parser;

    private BasketballStatsAdminService $statsAdminService;

    /**
     * @param \App\Service\OfficialBasketballBoxScoreParser|null $parser Parser dependency
     * @param \App\Service\BasketballStatsAdminService|null $statsAdminService Existing stat writer
     */
    public function __construct(
        ?OfficialBasketballBoxScoreParser $parser = null,
        ?BasketballStatsAdminService $statsAdminService = null,
    ) {
        $this->parser = $parser ?? new OfficialBasketballBoxScoreParser();
        $this->statsAdminService = $statsAdminService ?? new BasketballStatsAdminService();
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
     * Parse text and prepare editable rows for the importer screen.
     *
     * @param int $gameId Game ID
     * @param string $text Extracted PDF text
     * @return array<string, mixed>
     */
    public function preview(int $gameId, string $text): array
    {
        $viewData = $this->getAdminImportData($gameId);
        $parsed = $this->parser->parse($text);
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

        return $viewData + [
            'rawText' => $text,
            'parsed' => $parsed,
            'team' => $team,
            'opponent' => $opponent,
            'teamRows' => $teamRows,
            'opponentRows' => $opponentRows,
            'teamBox' => $team['totals'],
            'opponentBox' => $opponent['totals'],
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
        $personResult = $this->statsAdminService->saveAdminGamePersonRows($gameId, $teamRows, $addToTotals);
        $opponentResult = $this->statsAdminService->saveAdminGameOpponentRows($gameId, $opponentRows);

        $boxResult = $this->statsAdminService->saveAdminGameBox($gameId, [
            'team' => $this->normalizeBox((array)($data['team_box'] ?? [])),
            'opponent' => $this->normalizeBox((array)($data['opponent_box'] ?? [])),
            'add_to_totals' => $addToTotals,
            'team_minutes' => $data['team_minutes'] ?? 0,
        ]);
        if (!$boxResult['success']) {
            $errors[] = 'The team box score could not be saved.';
        }

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
        if ($text === '') {
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
     * @return array<string, mixed>
     */
    private function normalizeBox(array $box): array
    {
        $fields = [
            'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
            'AST', 'STL', 'BS', 'TRN', 'PF', 'TF', 'PTS', 'PNT', 'OTO',
            'SND', 'FB', 'BN', 'TIED', 'LC',
        ];
        $normalized = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $box) && $box[$field] !== '') {
                $normalized[$field] = $box[$field];
            }
        }

        return $normalized;
    }

    /**
     * @param list<array<string, mixed>> $teams Parsed teams
     * @param \App\Model\Entity\Game $game Game entity
     * @return int|null Parsed team index
     */
    private function resolveTeamIndex(array $teams, Game $game): ?int
    {
        $teamName = (string)($game->team_season->team->team_name ?? '');
        $opponentName = (string)($game->opponent->opponent_name ?? '');
        foreach ($teams as $index => $parsedTeam) {
            $label = $this->normalizeName((string)$parsedTeam['label']);
            if ($this->namesMatch($label, $this->normalizeName($teamName))) {
                return $index;
            }
            if ($this->namesMatch($label, $this->normalizeName($opponentName))) {
                return 1 - $index;
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
            static fn(array $row): bool => (string)$row['jersey'] === (string)$player['jersey'],
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
