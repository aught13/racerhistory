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
        'BS', 'BD',
    ];

    /**
     * @var list<string>
     */
    private const STAT_FIELDS = [
        'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
        'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL', 'BS', 'BD',
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
     *   teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>
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
            $missingHeaders = array_diff(self::HEADERS, $headers);
            if ($missingHeaders !== []) {
                throw new InvalidArgumentException(
                    'The CSV file is missing required columns: ' . implode(', ', $missingHeaders) . '.',
                );
            }

            $teams = [
                'team' => ['label' => 'Team', 'score' => null, 'players' => [], 'totals' => []],
                'opponent' => ['label' => 'Opponent', 'score' => null, 'players' => [], 'totals' => []],
            ];
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
                if (!in_array($rowType, ['player', 'totals'], true)) {
                    throw new InvalidArgumentException(
                        'CSV row ' . $rowNumber . ' must use player or totals as row_type.',
                    );
                }
                if (!isset($teams[$side])) {
                    throw new InvalidArgumentException('CSV row ' . $rowNumber . ' must use team or opponent as side.');
                }
                if ($this->isTemplatePlaceholder($data)) {
                    continue;
                }

                if ($rowType === 'player') {
                    $teams[$side]['players'][] = $this->parsePlayer($data, $rowNumber);
                    continue;
                }

                if ($teams[$side]['totals'] !== []) {
                    throw new InvalidArgumentException('The CSV file may include only one totals row for each side.');
                }
                $teams[$side]['totals'] = $this->parseTotals($data, $rowNumber);
                $teams[$side]['score'] = $teams[$side]['totals']['PTS'] ?? null;
            }
        } finally {
            fclose($stream);
        }

        foreach ($teams as $side => $team) {
            if ($team['players'] === []) {
                throw new InvalidArgumentException('The CSV file needs at least one player row for ' . $side . '.');
            }
            if ($team['totals'] === []) {
                throw new InvalidArgumentException('The CSV file needs one totals row for ' . $side . '.');
            }
        }

        return [
            'date' => null,
            'teams' => [$teams['team'], $teams['opponent']],
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
            $player[$field] = $this->parseStatValue((string)$data[$field], $field, $rowNumber);
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
        foreach (self::STAT_FIELDS as $field) {
            if ($field === 'MIN') {
                continue;
            }
            $value = $this->parseStatValue((string)$data[$field], $field, $rowNumber);
            $totals[$field] = is_int($value) ? $value : null;
        }
        if ($totals['PTS'] === null) {
            throw new InvalidArgumentException('CSV totals row ' . $rowNumber . ' must include PTS.');
        }

        return $totals;
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
