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
     * Keep the first final box-score page and exclude later period tables.
     *
     * @param string $text Extracted PDF text
     * @return string Final box-score section
     */
    private function extractFinalBoxScoreSection(string $text): string
    {
        foreach (['Official Basketball Play by Play', 'Quarter Starters:'] as $marker) {
            $markerPosition = strpos($text, $marker);
            if ($markerPosition !== false) {
                return substr($text, 0, $markerPosition);
            }
        }

        return $text;
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
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

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
     * Extract the two team names printed below the final box score.
     *
     * @param string $text Normalized extracted text
     * @return list<string>
     */
    private function extractTeamLabels(string $text): array
    {
        preg_match_all('/^(.+?)\s+-\s+\d+\s+Record:/m', $text, $matches);

        return array_values(array_unique(array_map('trim', $matches[1])));
    }
}
