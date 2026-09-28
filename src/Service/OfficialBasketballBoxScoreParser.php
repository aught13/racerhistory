<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Parses the final player and team totals from an NCAA LiveStats box score.
 *
 * The PDF export is intentionally treated as text input. PDF extraction is
 * handled outside the domain service so the importer can accept pasted text
 * from any PDF viewer without adding a server-side PDF dependency.
 */
class OfficialBasketballBoxScoreParser
{
    /**
     * Parse the final box-score blocks from extracted LiveStats text.
     *
     * @param string $text Extracted PDF text
     * @return array{
     *     date: string|null,
     *     teams: list<array{label: string, score: int|null, players: list<array<string, mixed>>, totals: array<string, int|null>}>}
     */
    public function parse(string $text): array
    {
        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = str_replace(["\xE2\x88\x92", "\xE2\x80\x93", "\xE2\x80\x94"], '-', $text);
        $text = $this->normalizeCompactTraditionalRows($text);
        if (str_contains($text, 'orb-drb') && str_contains($text, 'Game Information')) {
            return $this->parseHtmlBoxScoreFormat($text);
        }
        if (str_contains($text, 'Official Box Score') && str_contains($text, 'Game Totals -- Final Statistics')) {
            return $this->parseFinalStatisticsFormat($text);
        }
        if ($this->isLegacyGameTotalsFormat($text)) {
            return $this->parseLegacyGameTotals($text);
        }
        $columnarResult = $this->parseSmalotColumnarFormat($text);
        if ($columnarResult !== null) {
            return $columnarResult;
        }
        $finalSection = $this->extractFinalBoxScoreSection($text);
        $blocks = $this->extractPlayerBlocks($finalSection);
        if (count($blocks) < 2) {
            throw new InvalidArgumentException(
                'This does not look like an NCAA LiveStats box score. '
                . 'Upload the original PDF or paste text from its final box-score page.',
            );
        }

        $labels = $this->extractTeamLabels($finalSection);
        $summaryScores = $this->extractSummaryScores($finalSection);
        $blocks = $this->selectFinalBlocks($blocks, $summaryScores);
        $teams = [];
        foreach (array_slice($blocks, 0, 2) as $index => $block) {
            $totals = $block['totals'];
            $teams[] = [
                'label' => $labels[$index] ?? 'Team ' . ($index + 1),
                'score' => $totals['PTS'] ?? null,
                'players' => $block['players'],
                'totals' => $totals,
            ];
        }

        preg_match('/\b(\d{2}\/\d{2}\/\d{2})\b/', $text, $dateMatch);

        return [
            'date' => $dateMatch[1] ?? null,
            'teams' => $teams,
        ];
    }

    /**
     * Identify older StatCrew Game Totals and visitor/home table exports.
     *
     * @param string $text Extracted box-score text
     * @return bool Whether the text uses a legacy table format
     */
    private function isLegacyGameTotalsFormat(string $text): bool
    {
        return str_contains($text, 'Official Basketball Box Score -- Game Totals')
            || preg_match('/^(?:VISITORS|HOME TEAM):/im', $text) === 1
            || preg_match('/^(?:VISITORS|HOME TEAM):.+\n\s*TOT-FG\s+3-PT\s+REBOUNDS/im', $text) === 1;
    }

    /**
     * Parse the older NCAA Game Totals box-score layout.
     *
     * @param string $text Extracted PDF text
     * @return array{
     *     date: string|null,
     *     teams: list<array{label: string, score: int|null, players: list<array<string, mixed>>, totals: array<string, int|null>}>}
     */
    private function parseLegacyGameTotals(string $text): array
    {
        foreach (['Official Basketball Box Score -- 1st Half', 'Newspaper Box Score'] as $marker) {
            $markerPosition = strpos($text, $marker);
            if ($markerPosition !== false) {
                $text = substr($text, 0, $markerPosition);
                break;
            }
        }
        $teams = [];
        $current = null;

        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            if ($line === '') {
                continue;
            }

            $teamHeader = $this->parseLegacyTeamHeader($line);
            if ($teamHeader !== null) {
                if ($current !== null) {
                    $teams[] = $current;
                }
                $current = $teamHeader;
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^Totals[.\s]+/', $line) === 1) {
                $current['totals'] = $this->parseLegacyTotalsLine(
                    preg_replace('/^Totals[.\s]+/', 'Totals ', $line) ?? $line,
                );
                if ($current['score'] === null) {
                    $current['score'] = $current['totals']['PTS'] ?? null;
                }
                continue;
            }

            $player = $this->parseLegacyPlayerLine($line);
            if ($player !== null) {
                $current['players'][] = $player;
            }
        }

        if ($current !== null) {
            $teams[] = $current;
        }
        if (count($teams) < 2) {
            throw new InvalidArgumentException('This does not contain two NCAA Game Totals team tables.');
        }

        preg_match('/\b(\d{2}\/\d{2}\/\d{2})\b/', $text, $dateMatch);

        return [
            'date' => $dateMatch[1] ?? null,
            'teams' => array_slice($teams, 0, 2),
        ];
    }

    /**
     * Parse the modern Murray State athletics HTML box-score text format.
     *
     * @param string $text Extracted HTML text
     * @return array{date:string|null,teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>} Parsed result
     */
    private function parseHtmlBoxScoreFormat(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            static fn(string $line): bool => $line !== '',
        ));
        $headers = [];
        foreach ($lines as $index => $line) {
            if (preg_match('/^(.+?)\s+-\s+(\d+)$/', $line, $matches) === 1) {
                $headers[] = ['label' => trim($matches[1]), 'score' => (int)$matches[2], 'index' => $index];
            }
        }
        $headers = array_values(array_filter(
            $headers,
            static fn(array $header): bool => $header['label'] !== 'Team'
                && $header['label'] !== 'Total'
                && preg_match('/[A-Za-z]/', $header['label']) === 1,
        ));
        $headers = array_values(array_filter(
            $headers,
            function (array $header, int $index) use ($headers, $lines): bool {
                $end = $headers[$index + 1]['index'] ?? count($lines);
                $segment = implode("\n", array_slice($lines, $header['index'], $end - $header['index']));

                return str_contains($segment, "\nTotals\n");
            },
            ARRAY_FILTER_USE_BOTH,
        ));
        if (count($headers) < 2) {
            throw new InvalidArgumentException('This does not contain two HTML box-score team tables.');
        }

        $teams = [];
        foreach (array_slice($headers, 0, 2) as $index => $header) {
            $end = $headers[$index + 1]['index'] ?? count($lines);
            $teams[] = $this->parseHtmlBoxScoreTeam(
                array_slice($lines, $header['index'], $end - $header['index']),
                $header['label'],
                $header['score'],
            );
        }

        preg_match('/\b(\d{2}\/\d{2}\/\d{2})\b/', $text, $dateMatch);

        return ['date' => $dateMatch[1] ?? null, 'teams' => $teams];
    }

    /**
     * Parse one HTML box-score team section.
     *
     * @param list<string> $lines Team section lines
     * @param string $label Team label
     * @param int $score Team score
     * @return array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>} Parsed team
     */
    private function parseHtmlBoxScoreTeam(array $lines, string $label, int $score): array
    {
        $totalsIndex = array_search('Totals', $lines, true);
        $players = [];
        if (!is_int($totalsIndex)) {
            throw new InvalidArgumentException('The HTML box score is missing a totals row.');
        }

        for ($index = 0; $index < $totalsIndex; $index++) {
            if (preg_match('/^\d{1,2}$/', $lines[$index]) !== 1) {
                continue;
            }
            $name = $lines[$index + 1] ?? '';
            if ($name === '' || $name === 'Team' || $name === 'Totals') {
                continue;
            }
            $cursor = $index + 2;
            if (($lines[$cursor] ?? '') === '*') {
                $cursor++;
            }
            $minutes = $lines[$cursor] ?? '';
            $pairs = array_slice($lines, $cursor + 1, 4);
            $tail = array_slice($lines, $cursor + 5, 7);
            if (
                preg_match('/^\d+$/', $minutes) !== 1
                || count($pairs) !== 4
                || count($tail) !== 7
                || count(array_filter(
                    $pairs,
                    static fn(string $value): bool => preg_match('/^\d+\s*-\s*\d+$/', $value) === 1,
                )) !== 4
            ) {
                continue;
            }
            $pairValues = array_map(
                static fn(string $pair): array => array_map('intval', preg_split('/\s*-\s*/', $pair) ?: []),
                $pairs,
            );
            $players[] = [
                'jersey' => $lines[$index], 'name' => $name, 'MIN' => $minutes,
                'FGM' => $pairValues[0][0], 'FGA' => $pairValues[0][1],
                'TPM' => $pairValues[1][0], 'TPA' => $pairValues[1][1],
                'FTM' => $pairValues[2][0], 'FTA' => $pairValues[2][1],
                'ORB' => $pairValues[3][0], 'DRB' => $pairValues[3][1],
                'RB' => (int)$tail[0], 'PF' => (int)$tail[1], 'FD' => null,
                'AST' => (int)$tail[2], 'TRN' => (int)$tail[3], 'BS' => (int)$tail[4],
                'STL' => (int)$tail[5], 'PTS' => (int)$tail[6], 'BD' => null, 'PLUS_MINUS' => null,
            ];
            $index = $cursor + 11;
        }

        $totals = $this->parseHtmlBoxScoreTotals(array_slice($lines, $totalsIndex + 1, 13));

        return compact('label', 'score', 'players', 'totals');
    }

    /**
     * Parse an HTML box-score totals row.
     *
     * @param list<string> $lines Totals values
     * @return array<string,int|null> Totals
     */
    private function parseHtmlBoxScoreTotals(array $lines): array
    {
        $values = array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
        $values = array_slice($values, 0, 13);
        $pairs = array_map(
            static fn(string $pair): array => array_map('intval', preg_split('/\s*-\s*/', $pair) ?: []),
            array_slice($values, 1, 4),
        );
        $tail = array_slice($values, 5, 7);

        return [
            'FGM' => $pairs[0][0], 'FGA' => $pairs[0][1], 'TPM' => $pairs[1][0], 'TPA' => $pairs[1][1],
            'FTM' => $pairs[2][0], 'FTA' => $pairs[2][1], 'ORB' => $pairs[3][0], 'DRB' => $pairs[3][1],
            'RB' => (int)$tail[0], 'PF' => (int)$tail[1], 'FD' => null, 'AST' => (int)$tail[2],
            'TRN' => (int)$tail[3], 'BS' => (int)$tail[4], 'STL' => (int)$tail[5],
            'PTS' => (int)$tail[6], 'BD' => null, 'PLUS_MINUS' => null,
        ];
    }

    /**
     * Parse the 2019 Official Box Score Game Totals format.
     *
     * @param string $text Extracted box-score text
     * @return array{date:string|null,teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>} Parsed result
     */
    private function parseFinalStatisticsFormat(string $text): array
    {
        $text = preg_replace('/(?<=\d)-\s*\n\s*(?=\d)/', '-', $text) ?? $text;
        $teams = [];
        $current = null;
        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            if ($line === '') {
                continue;
            }

            if (
                preg_match('/^([A-Za-z].+?)\s+(\d+)$/', $line, $matches) === 1
                && !str_contains($line, 'Totals')
                && !str_starts_with($line, 'TEAM ')
                && !str_starts_with($line, 'TOTALS ')
            ) {
                if ($current !== null) {
                    $teams[] = $current;
                }
                $current = [
                    'label' => trim($matches[1]),
                    'score' => (int)$matches[2],
                    'players' => [],
                    'totals' => [],
                ];
                continue;
            }
            if ($current === null) {
                continue;
            }

            if (str_starts_with($line, 'TOTALS ')) {
                $current['totals'] = $this->parseFinalStatisticsTotals($line);
                continue;
            }
            $player = $this->parseFinalStatisticsPlayer($line);
            if ($player !== null) {
                $current['players'][] = $player;
            }
        }
        if ($current !== null) {
            $teams[] = $current;
        }
        if (count($teams) < 2) {
            throw new InvalidArgumentException('This does not contain two final-statistics team tables.');
        }

        preg_match('/\b([A-Z][a-z]+ \d{1,2}, \d{4})\b/', $text, $dateMatch);

        return [
            'date' => $dateMatch[1] ?? null,
            'teams' => array_slice($teams, 0, 2),
        ];
    }

    /**
     * Parse a player row from the final-statistics format.
     *
     * @param string $line Player row
     * @return array<string,mixed>|null Parsed row or null
     */
    private function parseFinalStatisticsPlayer(string $line): ?array
    {
        $pattern = '/^(\d{1,2})\s+(.+?)\s+[GFC]\s+(\d+)\s+(\d+)-(\d+)\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+'
            . '(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(-?\d+)$/';
        if (preg_match($pattern, $line, $matches) !== 1) {
            return null;
        }

        return [
            'jersey' => $matches[1], 'name' => trim($matches[2]), 'MIN' => $matches[18],
            'FGM' => (int)$matches[4], 'FGA' => (int)$matches[5],
            'TPM' => (int)$matches[6], 'TPA' => (int)$matches[7],
            'FTM' => (int)$matches[8], 'FTA' => (int)$matches[9],
            'ORB' => (int)$matches[10], 'DRB' => (int)$matches[11], 'RB' => (int)$matches[12],
            'PF' => (int)$matches[13], 'FD' => null, 'PTS' => (int)$matches[3],
            'AST' => (int)$matches[14], 'TRN' => (int)$matches[15],
            'STL' => (int)$matches[17], 'BS' => (int)$matches[16], 'BD' => null,
            'PLUS_MINUS' => (int)$matches[19],
        ];
    }

    /**
     * Parse a totals row from the final-statistics format.
     *
     * @param string $line Totals row
     * @return array<string,int|null> Totals
     */
    private function parseFinalStatisticsTotals(string $line): array
    {
        if (preg_match('/^TOTALS\s+(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(.+)$/', $line, $matches) !== 1) {
            return [];
        }
        $tail = array_map('intval', preg_split('/\s+/', $matches[8]) ?: []);
        if (count($tail) !== 9) {
            return [];
        }

        return [
            'PTS' => (int)$matches[1], 'FGM' => (int)$matches[2], 'FGA' => (int)$matches[3],
            'TPM' => (int)$matches[4], 'TPA' => (int)$matches[5], 'FTM' => (int)$matches[6],
            'FTA' => (int)$matches[7], 'ORB' => $tail[0], 'DRB' => $tail[1],
            'RB' => $tail[2], 'PF' => $tail[3], 'FD' => null,
            'AST' => $tail[4], 'TRN' => $tail[5], 'STL' => $tail[7],
            'BS' => null, 'BD' => null, 'PLUS_MINUS' => null,
        ];
    }

    /**
     * Parse Smalot's column-oriented extraction of a traditional LiveStats table.
     *
     * @param string $text Extracted PDF text
     * @return array{date:string|null,teams:list<array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}>}|null Parsed result or null when not columnar
     */
    private function parseSmalotColumnarFormat(string $text): ?array
    {
        if (!str_contains($text, "\n#\n") || !str_contains($text, "\nPlayer\n")) {
            return null;
        }

        $labels = $this->extractTeamLabels($text);
        if (count($labels) < 2) {
            return null;
        }

        $sections = [];
        foreach (array_slice($labels, 0, 2) as $label) {
            $teamPattern = '/^' . preg_quote($label, '/') . '[ \t]+(\d+)$/m';
            if (preg_match($teamPattern, $text, $match, PREG_OFFSET_CAPTURE) !== 1) {
                return null;
            }
            $sections[] = [
                'label' => $label,
                'score' => (int)$match[1][0],
                'offset' => $match[0][1],
            ];
        }

        $teams = [];
        foreach ($sections as $index => $section) {
            $start = $section['offset'];
            $end = $sections[$index + 1]['offset'] ?? strpos($text, '1st Half Play By Play', $start);
            $segment = substr($text, $start, $end === false ? null : $end - $start);
            $team = $this->parseSmalotColumnarTeam($segment, $section['label'], $section['score']);
            if ($team === null) {
                return null;
            }
            $teams[] = $team;
        }

        preg_match('/\b(\d{2}\/\d{2}\/\d{2})\b/', $text, $dateMatch);

        return [
            'date' => $dateMatch[1] ?? null,
            'teams' => $teams,
        ];
    }

    /**
     * Reconstruct one team from Smalot's vertical table columns.
     *
     * @param string $segment Team section
     * @param string $label Team label
     * @param int $score Team score
     * @return array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}|null Parsed team or null
     */
    private function parseSmalotColumnarTeam(string $segment, string $label, int $score): ?array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $segment)),
            static fn(string $line): bool => $line !== '',
        ));
        $numberHeader = array_search('#', $lines, true);
        $playerHeader = array_search('Player', $lines, true);
        $totalsHeader = array_search('Totals', $lines, true);
        if (!is_int($numberHeader) || !is_int($playerHeader) || !is_int($totalsHeader)) {
            return null;
        }

        $jerseys = array_values(array_filter(
            array_slice($lines, $numberHeader + 1, $playerHeader - $numberHeader - 1),
            static fn(string $line): bool => preg_match('/^(?:\d{1,2}|TM)$/', $line) === 1,
        ));
        $names = array_slice($lines, $playerHeader + 1, $totalsHeader - $playerHeader - 1);
        if (count($jerseys) < 2 || count($names) !== count($jerseys)) {
            return null;
        }

        $headers = ['MIN', 'FG', '3PT', 'FT', 'ORB-DRB', 'REB', 'PF', 'A', 'TO BLK', 'STL', 'PTS'];
        $columnIndexes = [];
        $searchFrom = $totalsHeader;
        foreach ($headers as $header) {
            $columnIndex = $this->findLineIndex($lines, $header, $searchFrom + 1);
            if ($columnIndex === null) {
                return null;
            }
            $columnIndexes[$header] = $columnIndex;
            $searchFrom = $columnIndex;
        }

        $columns = [];
        foreach ($headers as $header) {
            $stop = $this->findNextColumnHeader($lines, $columnIndexes[$header] + 1, $headers);
            $end = $stop === null ? null : $stop - $columnIndexes[$header] - 1;
            $valuePattern = match ($header) {
                'TO BLK' => '/^\d+$/',
                'FG', '3PT', 'FT', 'ORB-DRB' => '/^\d+-\d+$/',
                default => '/^\d+$/',
            };
            $values = $this->extractColumnValues(
                array_slice($lines, $columnIndexes[$header] + 1, $end),
                $valuePattern,
            );
            if ($header === 'TO BLK') {
                $half = intdiv(count($values), 2);
                $columns['TO'] = array_slice($values, 0, $half);
                $columns['BLK'] = array_slice($values, $half);
            } else {
                $columns[$header] = $values;
            }
        }

        foreach ($columns as $column) {
            if (count($column) !== count($jerseys) + 1) {
                return null;
            }
        }

        $players = [];
        $playerCount = count($jerseys) - 1;
        for ($index = 0; $index < $playerCount; $index++) {
            $fg = $this->splitColumnPair($columns['FG'][$index]);
            $threePoint = $this->splitColumnPair($columns['3PT'][$index]);
            $freeThrow = $this->splitColumnPair($columns['FT'][$index]);
            $rebounds = $this->splitColumnPair($columns['ORB-DRB'][$index]);
            if ($fg === null || $threePoint === null || $freeThrow === null || $rebounds === null) {
                return null;
            }
            $players[] = [
                'jersey' => $jerseys[$index],
                'name' => preg_replace('/^\*\s*/', '', $names[$index]) ?? $names[$index],
                'MIN' => $columns['MIN'][$index] ?? null,
                'FGM' => $fg[0], 'FGA' => $fg[1],
                'TPM' => $threePoint[0], 'TPA' => $threePoint[1],
                'FTM' => $freeThrow[0], 'FTA' => $freeThrow[1],
                'ORB' => $rebounds[0], 'DRB' => $rebounds[1],
                'RB' => (int)$columns['REB'][$index], 'PF' => (int)$columns['PF'][$index],
                'FD' => null, 'PTS' => (int)$columns['PTS'][$index],
                'AST' => (int)$columns['A'][$index], 'TRN' => (int)$columns['TO'][$index],
                'STL' => (int)$columns['STL'][$index], 'BS' => (int)$columns['BLK'][$index],
                'BD' => null, 'PLUS_MINUS' => null,
            ];
        }

        $totalsIndex = count($jerseys);
        $totals = $this->buildColumnarTotals($columns, $totalsIndex);
        if ($totals === null) {
            return null;
        }

        return compact('label', 'score', 'players', 'totals');
    }

    /**
     * Find an exact line after a given position.
     *
     * @param list<string> $lines Extracted lines
     * @param string $needle Header text
     * @param int $start Start index
     * @return int|null Matching index
     */
    private function findLineIndex(array $lines, string $needle, int $start): ?int
    {
        for ($index = $start, $count = count($lines); $index < $count; $index++) {
            if ($lines[$index] === $needle) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Find the next known column header.
     *
     * @param list<string> $lines Extracted lines
     * @param int $start Start index
     * @param list<string> $headers Header names
     * @return int|null Matching index
     */
    private function findNextColumnHeader(array $lines, int $start, array $headers): ?int
    {
        $indexes = [];
        foreach ($headers as $header) {
            $index = $this->findLineIndex($lines, $header, $start);
            if ($index !== null) {
                $indexes[] = $index;
            }
        }

        return $indexes === [] ? null : min($indexes);
    }

    /**
     * Extract values matching a column pattern.
     *
     * @param list<string> $lines Column lines
     * @param string $pattern Value pattern
     * @return list<string> Matching values
     */
    private function extractColumnValues(array $lines, string $pattern): array
    {
        return array_values(array_filter($lines, static fn(string $line): bool => preg_match($pattern, $line) === 1));
    }

    /**
     * Split a made-attempted pair such as 25-49.
     *
     * @param string $value Pair value
     * @return array{0:int,1:int}|null Pair or null
     */
    private function splitColumnPair(string $value): ?array
    {
        if (preg_match('/^(\d+)-(\d+)$/', $value, $matches) !== 1) {
            return null;
        }

        return [(int)$matches[1], (int)$matches[2]];
    }

    /**
     * Build totals from the final value in each reconstructed column.
     *
     * @param array<string, list<string>> $columns Reconstructed columns
     * @param int $index Totals row index
     * @return array<string, int|null>|null Totals or null when malformed
     */
    private function buildColumnarTotals(array $columns, int $index): ?array
    {
        $fg = $this->splitColumnPair($columns['FG'][$index]);
        $threePoint = $this->splitColumnPair($columns['3PT'][$index]);
        $freeThrow = $this->splitColumnPair($columns['FT'][$index]);
        $rebounds = $this->splitColumnPair($columns['ORB-DRB'][$index]);
        if ($fg === null || $threePoint === null || $freeThrow === null || $rebounds === null) {
            return null;
        }

        return [
            'FGM' => $fg[0], 'FGA' => $fg[1],
            'TPM' => $threePoint[0], 'TPA' => $threePoint[1],
            'FTM' => $freeThrow[0], 'FTA' => $freeThrow[1],
            'ORB' => $rebounds[0], 'DRB' => $rebounds[1],
            'RB' => (int)$columns['REB'][$index], 'PF' => (int)$columns['PF'][$index],
            'FD' => null, 'PTS' => (int)$columns['PTS'][$index],
            'AST' => (int)$columns['A'][$index], 'TRN' => (int)$columns['TO'][$index],
            'STL' => (int)$columns['STL'][$index], 'BS' => (int)$columns['BLK'][$index],
            'BD' => null, 'PLUS_MINUS' => null,
        ];
    }

    /**
     * Parse a legacy team heading from Game Totals or visitor/home exports.
     *
     * @param string $line Normalized source line
     * @return array{label:string,score:int|null,players:list<array<string,mixed>>,totals:array<string,int|null>}|null
     */
    private function parseLegacyTeamHeader(string $line): ?array
    {
        if (preg_match('/^(?:VISITORS|HOME TEAM):\s*(.+?)\s+\d+-\d+$/i', $line, $matches) === 1) {
            $label = trim($matches[1]);
            $score = null;
        } elseif (preg_match('/^HOME TEAM:\s*(.+)$/i', $line, $matches) === 1) {
            $label = trim($matches[1]);
            $score = null;
        } elseif (preg_match('/^(.+?)\s+(\d+)\s+\S+\s+\d+-\d+$/', $line, $matches) === 1) {
            $label = trim($matches[1]);
            $score = (int)$matches[2];
        } else {
            return null;
        }

        return [
            'label' => $label,
            'score' => $score,
                    'players' => [],
                    'totals' => [],
        ];
    }

    /**
     * Parse a player row from an older NCAA Game Totals export.
     *
     * @param string $line Player row
     * @return array<string, mixed>|null
     */
    private function parseLegacyPlayerLine(string $line): ?array
    {
        $pattern = '/^(\d+)\s+(.+?)(?:\s+[f-g])?\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '([\d\s-]+)$/i';
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $values = $this->parseLegacyTrailingValues(
            $matches[9],
            (int)$matches[3],
            (int)$matches[5],
            (int)$matches[7],
        );
        if ($values === null) {
            return null;
        }

        return [
            'jersey' => $matches[1],
            'name' => rtrim(trim($matches[2]), '.'),
            'MIN' => (string)$values[9],
            'FGM' => (int)$matches[3],
            'FGA' => (int)$matches[4],
            'TPM' => (int)$matches[5],
            'TPA' => (int)$matches[6],
            'FTM' => (int)$matches[7],
            'FTA' => (int)$matches[8],
            'ORB' => $values[0],
            'DRB' => $values[1],
            'RB' => $values[2],
            'PF' => $values[3],
            'FD' => null,
            'PTS' => $values[4],
            'AST' => $values[5],
            'TRN' => $values[6],
            'STL' => $values[8],
            'BS' => $values[7],
            'BD' => null,
            'PLUS_MINUS' => null,
        ];
    }

    /**
     * Restore legacy trailing fields when PDF extraction has merged numbers.
     *
     * @param string $trailingValues Rebounds through minutes
     * @param int $fieldGoalsMade Field goals made
     * @param int $threePointersMade Three-pointers made
     * @param int $freeThrowsMade Free throws made
     * @return list<int>|null
     */
    private function parseLegacyTrailingValues(
        string $trailingValues,
        int $fieldGoalsMade,
        int $threePointersMade,
        int $freeThrowsMade,
    ): ?array {
        $parts = preg_split('/\s+/', rtrim(trim($trailingValues), '-')) ?: [];
        if (count($parts) === 10) {
            return array_map('intval', $parts);
        }

        $digits = implode('', $parts);
        if (!ctype_digit($digits) || strlen($digits) < 10 || strlen($digits) > 18) {
            return null;
        }

        $values = $this->splitLegacyTrailingValues(
            $digits,
            0,
            [],
            $fieldGoalsMade,
            $threePointersMade,
            $freeThrowsMade,
        );

        return $values;
    }

    /**
     * @param string $digits Concatenated trailing stat values
     * @param int $offset Current character offset
     * @param list<int> $values Reconstructed values so far
     * @param int $fieldGoalsMade Field goals made
     * @param int $threePointersMade Three-pointers made
     * @param int $freeThrowsMade Free throws made
     * @return list<int>|null
     */
    private function splitLegacyTrailingValues(
        string $digits,
        int $offset,
        array $values,
        int $fieldGoalsMade,
        int $threePointersMade,
        int $freeThrowsMade,
    ): ?array {
        $field = count($values);
        if ($field === 10) {
            if ($offset !== strlen($digits)) {
                return null;
            }

            return $values[0] + $values[1] === $values[2]
                && $values[4] === (($fieldGoalsMade * 2) + $threePointersMade + $freeThrowsMade)
                ? $values
                : null;
        }

        $remainingFields = 10 - $field;
        $remainingDigits = strlen($digits) - $offset;
        if ($remainingDigits < $remainingFields) {
            return null;
        }

        $maxLength = min(3, $remainingDigits - $remainingFields + 1);
        for ($length = 1; $length <= $maxLength; $length++) {
            $value = (int)substr($digits, $offset, $length);
            if (($field === 3 && $value > 5) || ($field === 9 && $value > 99)) {
                continue;
            }

            $candidate = $this->splitLegacyTrailingValues(
                $digits,
                $offset + $length,
                [...$values, $value],
                $fieldGoalsMade,
                $threePointersMade,
                $freeThrowsMade,
            );
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Parse a totals row from an older NCAA Game Totals export.
     *
     * @param string $line Totals row
     * @return array<string, int|null>
     */
    private function parseLegacyTotalsLine(string $line): array
    {
        $pattern = '/^Totals\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(\d+(?:\s+\d+){9})$/';
        if (!preg_match($pattern, $line, $matches)) {
            return [];
        }

        $values = array_map('intval', preg_split('/\s+/', $matches[7]) ?: []);

        return [
            'FGM' => (int)$matches[1],
            'FGA' => (int)$matches[2],
            'TPM' => (int)$matches[3],
            'TPA' => (int)$matches[4],
            'FTM' => (int)$matches[5],
            'FTA' => (int)$matches[6],
            'ORB' => $values[0],
            'DRB' => $values[1],
            'RB' => $values[2],
            'PF' => $values[3],
            'FD' => null,
            'PTS' => $values[4],
            'AST' => $values[5],
            'TRN' => $values[6],
            'STL' => $values[8],
            'BS' => $values[7],
            'BD' => null,
            'PLUS_MINUS' => null,
        ];
    }

    /**
     * Keep the first final box-score page and exclude later period tables.
     *
     * @param string $text Extracted PDF text
     * @return string Final box-score section
     */
    private function extractFinalBoxScoreSection(string $text): string
    {
        foreach (['Official Basketball Play by Play', '1st Half Play By Play', 'Quarter Starters:'] as $marker) {
            $markerPosition = strpos($text, $marker);
            if ($markerPosition !== false) {
                return substr($text, 0, $markerPosition);
            }
        }

        return $text;
    }

    /**
     * Restore separators removed by Smalot PDF Parser from traditional rows.
     *
     * @param string $text Extracted PDF text
     * @return string Text with compact player and totals rows normalized
     */
    private function normalizeCompactTraditionalRows(string $text): string
    {
        $lines = [];
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            $line = preg_replace(
                '/^(\d{1,2})([A-Za-z][A-Za-z .,\'-]*?)\s*(\*)?\s*(\d{2,3})(?=\d+-\d+)/',
                '$1 $2 $3 $4 ',
                $line,
            ) ?? $line;
            $line = preg_replace(
                '/^Totals\s+-?\s*(\d{2,3})(?=\d{2,}-\d+)/',
                'Totals - $1 ',
                $line,
            ) ?? $line;
            $line = $this->normalizeCompactTraditionalStatTail($line);
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Restore the seven traditional stat columns after ORB-DRB.
     *
     * @param string $line Traditional player or totals row
     * @return string Row with the compact stat tail expanded
     */
    private function normalizeCompactTraditionalStatTail(string $line): string
    {
        $pattern = '/^(\d+\s+.+?\s+\d{2,3}\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+))\s+(.+)$/';
        if (preg_match($pattern, $line, $matches) !== 1) {
            $pattern = '/^(Totals\s+-\s+\d+\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
                . '(\d+)-(\d+)\s+(\d+)-(\d+))\s+(.+)$/';
            if (preg_match($pattern, $line, $matches) !== 1) {
                return $line;
            }
        }

        $tailTokens = preg_split('/\s+/', trim($matches[10])) ?: [];
        if (count($tailTokens) === 7) {
            return $line;
        }

        $fieldGoalsMade = (int)$matches[2];
        $threePointersMade = (int)$matches[4];
        $freeThrowsMade = (int)$matches[6];
        $expectedPoints = (string)(($fieldGoalsMade * 2) + $threePointersMade + $freeThrowsMade);
        $digits = preg_replace('/\D/', '', implode('', $tailTokens)) ?? '';
        if (!str_ends_with($digits, $expectedPoints)) {
            return $line;
        }

        $prefix = substr($digits, 0, -strlen($expectedPoints));
        $values = $this->splitCompactTraditionalTail($prefix, 0, 6, []);
        if ($values === null) {
            return $line;
        }

        $rowPrefix = $matches[1];

        return $rowPrefix . ' ' . implode(' ', [...$values, (int)$expectedPoints]);
    }

    /**
     * Split compact rebound and counting stats into six values.
     *
     * @param string $digits Compact stat digits
     * @param int $offset Current digit offset
     * @param int $remainingFields Fields left to split
     * @param list<int> $values Values already split
     * @return list<int>|null Split values or null when invalid
     */
    private function splitCompactTraditionalTail(
        string $digits,
        int $offset,
        int $remainingFields,
        array $values,
    ): ?array {
        $remainingDigits = strlen($digits) - $offset;
        if ($remainingFields === 0) {
            return $remainingDigits === 0 ? $values : null;
        }
        if ($remainingDigits < $remainingFields || $remainingDigits > $remainingFields * 2) {
            return null;
        }

        for ($length = 2; $length >= 1; $length--) {
            if ($remainingDigits - $length < $remainingFields - 1) {
                continue;
            }
            $candidate = $this->splitCompactTraditionalTail(
                $digits,
                $offset + $length,
                $remainingFields - 1,
                [...$values, (int)substr($digits, $offset, $length)],
            );
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Extract the first two player blocks, which are the final team tables.
     *
     * @param string $text Normalized extracted text
     * @return list<array{players: list<array<string, mixed>>, totals: array<string, int|null>}>
     */
    private function extractPlayerBlocks(string $text): array
    {
        $streamBlocks = $this->extractPlayerBlocksFromStream($text);
        if (count($streamBlocks) >= 2) {
            return $streamBlocks;
        }

        $blocks = [];
        $players = [];
        $totals = null;

        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            if ($line === '') {
                continue;
            }

            $player = $this->parsePlayerLine($line);
            if ($player !== null) {
                $players[] = $player;
                continue;
            }

            if (str_starts_with($line, 'Totals ')) {
                $totals = $this->parseTotalsLine($line);
                continue;
            }

            if ($totals !== null && $players !== []) {
                $blocks[] = ['players' => $players, 'totals' => $totals];
                $players = [];
                $totals = null;
            }
        }

        if ($totals !== null && $players !== []) {
            $blocks[] = ['players' => $players, 'totals' => $totals];
        }

        if (count($blocks) < 2) {
            return $this->extractPlayerBlocksFromStream($text);
        }

        return $this->selectFinalBlocks($blocks);
    }

    /**
     * Recover final tables when PDF extraction wraps rows across lines.
     *
     * @param string $text Extracted PDF text
     * @return list<array{players: list<array<string, mixed>>, totals: array<string, int|null>}>
     */
    private function extractPlayerBlocksFromStream(string $text): array
    {
        $repairedLines = array_map(
            fn(string $line): string => $this->repairCompactFreeThrowColumns($line),
            explode("\n", $text),
        );
        $stream = trim(preg_replace('/\s+/', ' ', implode("\n", $repairedLines)) ?? $text);
        $playerPattern = '/(\d+)\s+([A-Za-z][A-Za-z .,\'-]*?)\s+(?:[FGC])?\s*(\d{1,2}:\d{2})\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(-?\d+(?:\s+-?\d+){11})/';
        $totalsPattern = '/Totals\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(-?\d+(?:\s+-?\d+){11})/';

        preg_match_all($playerPattern, $stream, $playerMatches, PREG_OFFSET_CAPTURE);
        preg_match_all($totalsPattern, $stream, $totalMatches, PREG_OFFSET_CAPTURE);
        $blocks = [];
        $playerIndex = 0;
        $previousTotalEnd = 0;

        foreach ($totalMatches[0] as $totalIndex => $totalMatch) {
            $totalOffset = $totalMatch[1];
            $players = [];
            while (isset($playerMatches[0][$playerIndex])) {
                $playerOffset = $playerMatches[0][$playerIndex][1];
                if ($playerOffset >= $totalOffset) {
                    break;
                }
                if ($playerOffset >= $previousTotalEnd) {
                    $players[] = $this->buildPlayerFromMatch($playerMatches, $playerIndex);
                }
                $playerIndex++;
            }

            if ($players !== []) {
                $blocks[] = [
                    'players' => $players,
                    'totals' => $this->buildTotalsFromMatch($totalMatches, $totalIndex),
                ];
            }
            $previousTotalEnd = $totalOffset + strlen($totalMatch[0]);
        }

        return $blocks;
    }

    /**
     * Restore a missing separator when PDF layout concatenates FTA and ORB.
     *
     * @param string $text Normalized extracted text
     * @return string Repaired text
     */
    private function repairCompactFreeThrowColumns(string $text): string
    {
        $pattern = '/^(\d+\s+[A-Za-z][A-Za-z .\'-]*?\s+(?:[FGC])?\s*\d{1,2}:\d{2}\s+'
            . '\d+-\d+\s+\d+-\d+\s+)(\d+)-(\d+)\s+(.*)$/';
        if (!preg_match($pattern, trim($text), $matches)) {
            return $text;
        }

        $tokens = preg_split('/\s+/', trim($matches[4])) ?: [];
        $numericTail = [];
        $suffix = [];
        foreach ($tokens as $token) {
            if ($suffix === [] && preg_match('/^-?\d+$/', $token)) {
                $numericTail[] = $token;
            } else {
                $suffix[] = $token;
            }
        }
        if (count($numericTail) !== 11 || strlen($matches[3]) < 2) {
            return $text;
        }

        $repairedTail = array_merge([substr($matches[3], -1)], $numericTail, $suffix);

        return $matches[1] . $matches[2] . '-' . substr($matches[3], 0, -1)
            . ' ' . implode(' ', $repairedTail);
    }

    /**
     * Select blocks matching the explicit final summary scores when available.
     *
     * Falls back to the highest-scoring adjacent pair for incomplete extracts.
     *
     * @param list<array{players: list<array<string, mixed>>, totals: array<string, int|null>}> $blocks Detected box-score blocks
     * @param list<int> $summaryScores Explicit final team scores
     * @return list<array{players: list<array<string, mixed>>, totals: array<string, int|null>}>
     */
    private function selectFinalBlocks(array $blocks, array $summaryScores = []): array
    {
        if (count($summaryScores) >= 2) {
            $selected = [];
            foreach (array_slice($summaryScores, 0, 2) as $summaryScore) {
                foreach ($blocks as $index => $block) {
                    if ((int)($block['totals']['PTS'] ?? -1) === $summaryScore && !in_array($index, $selected, true)) {
                        $selected[] = $index;
                        break;
                    }
                }
            }
            if (count($selected) === 2) {
                return [$blocks[$selected[0]], $blocks[$selected[1]]];
            }
        }

        $bestStart = 0;
        $bestScore = -1;
        $blockCount = count($blocks);
        for ($index = 0; $index < $blockCount - 1; $index++) {
            $score = (int)($blocks[$index]['totals']['PTS'] ?? 0)
                + (int)($blocks[$index + 1]['totals']['PTS'] ?? 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestStart = $index;
            }
        }

        return array_slice($blocks, $bestStart, 2);
    }

    /**
     * Extract final scores printed with the team records.
     *
     * @param string $text Extracted PDF text
     * @return list<int>
     */
    private function extractSummaryScores(string $text): array
    {
        preg_match_all('/^.+?\s+-\s+(\d+)\s+Record:/m', $text, $matches);

        return array_map('intval', $matches[1]);
    }

    /**
     * Parse one LiveStats final player row.
     *
     * @param string $line Text row
     * @return array<string, mixed>|null
     */
    private function parsePlayerLine(string $line): ?array
    {
        $pattern = '/^(\d+)\s+(.+?)\s+(\d{1,2}:\d{2})\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(-?\d+(?:\s+-?\d+){11})$/';
        if (preg_match($pattern, $line, $matches) === 1) {
            return $this->buildPlayerFields(
                $matches[1],
                $matches[2],
                $matches[3],
                (int)$matches[4],
                (int)$matches[5],
                (int)$matches[6],
                (int)$matches[7],
                (int)$matches[8],
                (int)$matches[9],
                $matches[10],
            );
        }

        $traditionalPattern = '/^(\d+)\s+([A-Za-z][A-Za-z .,\'-]*?)\s+\*?\s*'
            . '(\d{1,3}(?::\d{2})?)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)\s+(\d+)\s+'
            . '(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/';
        if (preg_match($traditionalPattern, $line, $matches) !== 1) {
            return null;
        }

        return [
            'jersey' => $matches[1],
            'name' => trim($matches[2]),
            'MIN' => $matches[3],
            'FGM' => (int)$matches[4],
            'FGA' => (int)$matches[5],
            'TPM' => (int)$matches[6],
            'TPA' => (int)$matches[7],
            'FTM' => (int)$matches[8],
            'FTA' => (int)$matches[9],
            'ORB' => (int)$matches[10],
            'DRB' => (int)$matches[11],
            'RB' => (int)$matches[12],
            'PF' => (int)$matches[13],
            'FD' => null,
            'PTS' => (int)$matches[18],
            'AST' => (int)$matches[14],
            'TRN' => (int)$matches[15],
            'STL' => (int)$matches[17],
            'BS' => (int)$matches[16],
            'BD' => null,
            'PLUS_MINUS' => null,
        ];
    }

    /**
     * @param array<int, array<int, array{0: string, 1: int}>> $matches Player matches
     * @param int $index Match index
     * @return array<string, mixed>
     */
    private function buildPlayerFromMatch(array $matches, int $index): array
    {
        return $this->buildPlayerFields(
            $matches[1][$index][0],
            $matches[2][$index][0],
            $matches[3][$index][0],
            (int)$matches[4][$index][0],
            (int)$matches[5][$index][0],
            (int)$matches[6][$index][0],
            (int)$matches[7][$index][0],
            (int)$matches[8][$index][0],
            (int)$matches[9][$index][0],
            $matches[10][$index][0],
        );
    }

    /**
     * @param string $jersey Jersey number
     * @param string $name Player name
     * @param string $minutes Minutes played
     * @param int $fgm Field goals made
     * @param int $fga Field goals attempted
     * @param int $tpm Three-pointers made
     * @param int $tpa Three-pointers attempted
     * @param int $ftm Free throws made
     * @param int $fta Free throws attempted
     * @param string $tail Remaining stat columns
     * @return array<string, mixed>
     */
    private function buildPlayerFields(
        string $jersey,
        string $name,
        string $minutes,
        int $fgm,
        int $fga,
        int $tpm,
        int $tpa,
        int $ftm,
        int $fta,
        string $tail,
    ): array {
        $tailValues = array_map('intval', preg_split('/\s+/', $tail) ?: []);

        return [
            'jersey' => $jersey,
            'name' => trim($name),
            'MIN' => $minutes,
            'FGM' => $fgm,
            'FGA' => $fga,
            'TPM' => $tpm,
            'TPA' => $tpa,
            'FTM' => $ftm,
            'FTA' => $fta,
            'ORB' => $tailValues[0] ?? null,
            'DRB' => $tailValues[1] ?? null,
            'RB' => $tailValues[2] ?? null,
            'PF' => $tailValues[3] ?? null,
            'FD' => $tailValues[4] ?? null,
            'PTS' => $tailValues[5] ?? null,
            'AST' => $tailValues[6] ?? null,
            'TRN' => $tailValues[7] ?? null,
            'STL' => $tailValues[8] ?? null,
            'BS' => $tailValues[9] ?? null,
            'BD' => $tailValues[10] ?? null,
            'PLUS_MINUS' => $tailValues[11] ?? null,
        ];
    }

    /**
     * @param array<int, array<int, array{0: string, 1: int}>> $matches Totals matches
     * @param int $index Match index
     * @return array<string, int|null>
     */
    private function buildTotalsFromMatch(array $matches, int $index): array
    {
        $values = array_map('intval', preg_split('/\s+/', $matches[7][$index][0]) ?: []);

        return [
            'FGM' => (int)$matches[1][$index][0],
            'FGA' => (int)$matches[2][$index][0],
            'TPM' => (int)$matches[3][$index][0],
            'TPA' => (int)$matches[4][$index][0],
            'FTM' => (int)$matches[5][$index][0],
            'FTA' => (int)$matches[6][$index][0],
            'ORB' => $values[0] ?? null,
            'DRB' => $values[1] ?? null,
            'RB' => $values[2] ?? null,
            'PF' => $values[3] ?? null,
            'FD' => $values[4] ?? null,
            'PTS' => $values[5] ?? null,
            'AST' => $values[6] ?? null,
            'TRN' => $values[7] ?? null,
            'STL' => $values[8] ?? null,
            'BS' => $values[9] ?? null,
            'BD' => $values[10] ?? null,
            'PLUS_MINUS' => $values[11] ?? null,
        ];
    }

    /**
     * Parse the aggregate totals row using the same column order as player rows.
     *
     * @param string $line Totals row
     * @return array<string, int|null>
     */
    private function parseTotalsLine(string $line): array
    {
        $pattern = '/^Totals\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(-?\d+(?:\s+-?\d+){11})$/';
        if (preg_match($pattern, $line, $matches) === 1) {
            $values = array_map('intval', preg_split('/\s+/', $matches[7]) ?: []);

            return [
                'FGM' => (int)$matches[1],
                'FGA' => (int)$matches[2],
                'TPM' => (int)$matches[3],
                'TPA' => (int)$matches[4],
                'FTM' => (int)$matches[5],
                'FTA' => (int)$matches[6],
                'ORB' => $values[0] ?? null,
                'DRB' => $values[1] ?? null,
                'RB' => $values[2] ?? null,
                'PF' => $values[3] ?? null,
                'FD' => $values[4] ?? null,
                'PTS' => $values[5] ?? null,
                'AST' => $values[6] ?? null,
                'TRN' => $values[7] ?? null,
                'STL' => $values[8] ?? null,
                'BS' => $values[9] ?? null,
                'BD' => $values[10] ?? null,
                'PLUS_MINUS' => $values[11] ?? null,
            ];
        }

        $traditionalPattern = '/^Totals\s+-\s+\d+\s+(\d+)-(\d+)\s+(\d+)-(\d+)\s+'
            . '(\d+)-(\d+)\s+(\d+)-(\d+)\s+(\d+)\s+(\d+)\s+'
            . '(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/';
        if (preg_match($traditionalPattern, $line, $matches) !== 1) {
            return [];
        }

        return [
            'FGM' => (int)$matches[1],
            'FGA' => (int)$matches[2],
            'TPM' => (int)$matches[3],
            'TPA' => (int)$matches[4],
            'FTM' => (int)$matches[5],
            'FTA' => (int)$matches[6],
            'ORB' => (int)$matches[7],
            'DRB' => (int)$matches[8],
            'RB' => (int)$matches[9],
            'PF' => (int)$matches[10],
            'FD' => null,
            'PTS' => (int)$matches[15],
            'AST' => (int)$matches[11],
            'TRN' => (int)$matches[12],
            'STL' => (int)$matches[14],
            'BS' => (int)$matches[13],
            'BD' => null,
            'PLUS_MINUS' => null,
        ];
    }

    /**
     * Extract the two team names printed below the final box score.
     *
     * @param string $text Normalized extracted text
     * @return list<string>
     */
    private function extractTeamLabels(string $text): array
    {
        preg_match_all('/^(.+?)\s+-\s+\d+\s+Record:/m', $text, $matches);
        if ($matches[1] === []) {
            preg_match('/^(.+?)\s+\([^\n]+\)\s+-vs-\s+(.+?)\s+\([^\n]+\)$/m', $text, $matches);

            return isset($matches[1], $matches[2])
                ? [trim($matches[1]), trim($matches[2])]
                : [];
        }

        return array_values(array_unique(array_map('trim', $matches[1])));
    }
}
