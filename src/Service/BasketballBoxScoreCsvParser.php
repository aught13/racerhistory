<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Parses the editable CSV fallback for basketball box-score imports.
 */
class BasketballBoxScoreCsvParser
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'row_type', 'side', 'jersey', 'name', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA',
        'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL',
        'BS', 'BD', 'period', 'field', 'value', 'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
    ];

    /**
     * @var list<string>
     */
    private const STAT_FIELDS = [
        'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
        'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL', 'BS', 'BD',
    ];

    /**
     * @var list<string>
     */
    private const BOX_FIELDS = [
        'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'PF', 'PTS',
        'AST', 'TRN', 'STL', 'BS', 'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
    ];

    /**
     * Build a CSV template with placeholders for both participating teams.
     *
     * @return string CSV template contents
     */
    public function template(): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new InvalidArgumentException('The CSV template could not be created.');
        }

        try {
            fputcsv($stream, self::HEADERS, ',', '"', '');
            foreach ([['player', 'team'], ['totals', 'team'], ['player', 'opponent'], ['totals', 'opponent']] as $row) {
                fputcsv($stream, array_pad($row, count(self::HEADERS), ''), ',', '"', '');
            }
            foreach (['team', 'opponent'] as $side) {
                $row = array_fill_keys(self::HEADERS, '');
                $row['row_type'] = 'period';
                $row['side'] = $side;
                $row['period'] = '1';
                fputcsv($stream, array_values($row), ',', '"', '');
            }
            foreach (['attendance', 'period_1_team', 'period_1_opponent', 'official_1'] as $field) {
                $row = array_fill_keys(self::HEADERS, '');
                $row['row_type'] = 'game';
                $row['side'] = 'game';
                $row['field'] = $field;
                fputcsv($stream, array_values($row), ',', '"', '');
            }
            rewind($stream);
            $contents = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        if ($contents === false) {
            throw new InvalidArgumentException('The CSV template could not be created.');
        }

        return $contents;
    }

    /**
     * Parse a structured CSV document into the standard box-score contract.
     *
     * @param string $contents CSV document contents
     * @return array{
     *   date:null,
     *   teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>,
     *   game_results:array{attendance:string|null,officials:array<int|string,string>,period_scores:array<string,array<string,int>>},
     *   period_boxes:array<string,array<string,array<string,int|null>>>
     * }
     */
    public function parse(string $contents): array
    {
        if (trim($contents) === '') {
            throw new InvalidArgumentException('The CSV file is empty.');
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false || fwrite($stream, $contents) === false) {
            throw new InvalidArgumentException('The CSV file could not be read.');
        }

        try {
            rewind($stream);
            $headers = fgetcsv($stream, null, ',', '"', '');
            if (!is_array($headers)) {
                throw new InvalidArgumentException('The CSV file must begin with a header row.');
            }
            $headers = array_map(
                static fn(mixed $header): string => trim(str_replace("\xEF\xBB\xBF", '', (string)$header)),
                $headers,
            );
            $requiredHeaders = array_slice(self::HEADERS, 0, 22);
            $missingHeaders = array_diff($requiredHeaders, $headers);
            if ($missingHeaders !== []) {
                throw new InvalidArgumentException(
                    'The CSV file is missing required columns: ' . implode(', ', $missingHeaders) . '.',
                );
            }

            $teams = [
                'team' => ['label' => 'Team', 'score' => null, 'players' => [], 'totals' => []],
                'opponent' => ['label' => 'Opponent', 'score' => null, 'players' => [], 'totals' => []],
            ];
            $gameResults = ['attendance' => null, 'officials' => [], 'period_scores' => []];
            $periodBoxes = [];
            $hasData = false;
            $rowNumber = 1;
            while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $rowNumber++;
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $values = array_pad($row, count($headers), '');
                $data = array_combine($headers, $values);

                $rowType = strtolower(trim((string)$data['row_type']));
                $side = strtolower(trim((string)$data['side']));
                if (!in_array($rowType, ['player', 'totals', 'period', 'game'], true)) {
                    throw new InvalidArgumentException(
                        'CSV row ' . $rowNumber . ' must use player, totals, period, or game as row_type.',
                    );
                }
                if ($rowType === 'game') {
                    if ($side !== 'game') {
                        throw new InvalidArgumentException('CSV game rows must use game as side.');
                    }
                    $gameRow = $this->parseGameRow($data, $rowNumber);
                    if (isset($gameRow['attendance'])) {
                        $gameResults['attendance'] = $gameRow['attendance'];
                    }
                    $gameResults['officials'] = array_replace(
                        $gameResults['officials'],
                        $gameRow['officials'] ?? [],
                    );
                    foreach ($gameRow['period_scores'] ?? [] as $period => $scores) {
                        $gameResults['period_scores'][$period] = array_replace(
                            $gameResults['period_scores'][$period] ?? [],
                            $scores,
                        );
                    }
                    $hasData = $hasData || $gameRow !== [];
                    continue;
                }
                if (!isset($teams[$side])) {
                    throw new InvalidArgumentException('CSV row ' . $rowNumber . ' must use team or opponent as side.');
                }
                if ($this->isTemplatePlaceholder($data)) {
                    continue;
                }

                if ($rowType === 'player') {
                    $teams[$side]['players'][] = $this->parsePlayer($data, $rowNumber);
                    $hasData = true;
                    continue;
                }

                if ($rowType === 'period') {
                    $period = $this->normalizePeriod((string)($data['period'] ?? ''), $rowNumber);
                    $stats = $this->parseBoxStats($data, $rowNumber);
                    if ($stats !== []) {
                        $periodBoxes[$period][$side] = $stats;
                        if (isset($stats['PTS'])) {
                            $gameResults['period_scores'][$period][$side] = (int)$stats['PTS'];
                        }
                        $hasData = true;
                    }
                    continue;
                }

                if ($teams[$side]['totals'] !== []) {
                    throw new InvalidArgumentException('The CSV file may include only one totals row for each side.');
                }
                $teams[$side]['totals'] = $this->parseTotals($data, $rowNumber);
                $teams[$side]['score'] = $teams[$side]['totals']['PTS'] ?? null;
                $hasData = $hasData || $teams[$side]['totals'] !== [];
            }
        } finally {
            fclose($stream);
        }

        if (!$hasData) {
            throw new InvalidArgumentException(
                'The CSV file needs at least one completed player, totals, period, or game row.',
            );
        }

        return [
            'date' => null,
            'teams' => [$teams['team'], $teams['opponent']],
            'game_results' => $gameResults,
            'period_boxes' => $periodBoxes,
        ];
    }

    /**
     * @param list<string|null> $row CSV row values
     * @return bool Whether the row has no values
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,string> $data CSV row keyed by column name
     * @return bool Whether the row is a blank row from the downloaded template
     */
    private function isTemplatePlaceholder(array $data): bool
    {
        foreach (self::HEADERS as $header) {
            if (in_array($header, ['row_type', 'side'], true)) {
                continue;
            }
            if (trim((string)($data[$header] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,string> $data CSV row keyed by column name
     * @param int $rowNumber CSV line number
     * @return array<string,mixed>
     */
    private function parsePlayer(array $data, int $rowNumber): array
    {
        $name = trim((string)$data['name']);
        if ($name === '') {
            throw new InvalidArgumentException('CSV player row ' . $rowNumber . ' must include a name.');
        }

        $player = [
            'jersey' => trim((string)$data['jersey']),
            'name' => $name,
        ];
        foreach (self::STAT_FIELDS as $field) {
            $player[$field] = $this->parseStatValue((string)($data[$field] ?? ''), $field, $rowNumber);
        }

        return $player;
    }

    /**
     * @param array<string,string> $data CSV row keyed by column name
     * @param int $rowNumber CSV line number
     * @return array<string,int|null>
     */
    private function parseTotals(array $data, int $rowNumber): array
    {
        $totals = [];
        foreach (self::BOX_FIELDS as $field) {
            $value = $this->parseStatValue((string)($data[$field] ?? ''), $field, $rowNumber);
            if ($value !== null) {
                $totals[$field] = (int)$value;
            }
        }

        return $totals;
    }

    /**
     * Parse one optional game-result CSV row.
     *
     * @param array<string,string> $data CSV row keyed by header
     * @param int $rowNumber CSV line number
     * @return array{attendance?:string,officials?:array<int|string,string>,period_scores?:array<string,array<string,int>>} Parsed result values
     */
    private function parseGameRow(array $data, int $rowNumber): array
    {
        $field = strtolower(trim((string)($data['field'] ?? '')));
        $value = trim((string)($data['value'] ?? ''));
        if ($field === '' || $value === '') {
            return [];
        }
        if ($field === 'attendance') {
            return ['attendance' => str_replace(',', '', $value)];
        }
        if (preg_match('/^official_(\d+)$/', $field, $matches) === 1) {
            return ['officials' => [$field => $value]];
        }
        if (preg_match('/^(period|overtime)_(\d+)_(team|opponent)$/', $field, $matches) === 1) {
            if (preg_match('/^\d+$/', $value) !== 1) {
                throw new InvalidArgumentException('CSV game row ' . $rowNumber . ' has an invalid period score.');
            }
            $period = $matches[1] === 'period'
                ? (string)(int)$matches[2]
                : 'OT' . ((int)$matches[2] > 1 ? (string)(int)$matches[2] : '');

            return ['period_scores' => [$period => [$matches[3] => (int)$value]]];
        }

        throw new InvalidArgumentException('CSV game row ' . $rowNumber . ' has an unsupported field.');
    }

    /**
     * Parse optional statistics from a period row.
     *
     * @param array<string,string> $data CSV row keyed by header
     * @param int $rowNumber CSV line number
     * @return array<string,int> Present statistics
     */
    private function parseBoxStats(array $data, int $rowNumber): array
    {
        $stats = [];
        foreach (self::BOX_FIELDS as $field) {
            $value = $this->parseStatValue((string)($data[$field] ?? ''), $field, $rowNumber);
            if ($value !== null) {
                $stats[$field] = (int)$value;
            }
        }

        return $stats;
    }

    /**
     * Validate and normalize a period identifier from a CSV row.
     *
     * @param string $period Source period value
     * @param int $rowNumber CSV line number
     * @return string Database period code
     */
    private function normalizePeriod(string $period, int $rowNumber): string
    {
        $period = strtoupper(trim($period));
        if (preg_match('/^\d+$/', $period) === 1) {
            return (string)(int)$period;
        }
        if (preg_match('/^OT\s*(\d*)$/', $period, $matches) === 1) {
            $overtime = (int)$matches[1];

            return $overtime > 1 ? 'OT' . $overtime : 'OT';
        }

        throw new InvalidArgumentException('CSV row ' . $rowNumber . ' has an invalid period.');
    }

    /**
     * @param string $value Source CSV value
     * @param string $field Stat field name
     * @param int $rowNumber CSV line number
     * @return string|int|null Parsed stat value
     */
    private function parseStatValue(string $value, string $field, int $rowNumber): int|string|null
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if ($field === 'MIN') {
            if (preg_match('/^\d+(?::[0-5]\d|(?:\.\d{1,2})?)$/', $value) !== 1) {
                throw new InvalidArgumentException('CSV row ' . $rowNumber . ' has an invalid MIN value.');
            }

            return $value;
        }
        if (preg_match('/^\d+$/', $value) !== 1) {
            throw new InvalidArgumentException('CSV row ' . $rowNumber . ' has an invalid ' . $field . ' value.');
        }

        return (int)$value;
    }
}
