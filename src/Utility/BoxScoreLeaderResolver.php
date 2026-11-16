<?php
declare(strict_types=1);

namespace App\Utility;

final class BoxScoreLeaderResolver
{
    /**
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @param bool $lowerIsBetter
     * @return array{left: bool, right: bool}
     */
    public static function compareValues(
        mixed $leftValue,
        mixed $rightValue,
        bool $lowerIsBetter = false,
    ): array {
        $leftScore = self::scoreFromValue($leftValue);
        $rightScore = self::scoreFromValue($rightValue);
        if ($leftScore === null || $rightScore === null) {
            return ['left' => false, 'right' => false];
        }

        if ($leftScore === $rightScore) {
            $isLeader = $leftScore > 0;

            return ['left' => $isLeader, 'right' => $isLeader];
        }

        $leftIsLeader = $lowerIsBetter
            ? $leftScore < $rightScore
            : $leftScore > $rightScore;

        return ['left' => $leftIsLeader, 'right' => !$leftIsLeader];
    }

    /**
     * @template TRow
     * @param list<TRow> $rows
     * @param list<array{key: string, format: string}> $columns
     * @param callable(TRow, array{key: string, format: string}): mixed $resolveValue
     * @param callable(mixed, TRow, array{key: string, format: string}): string $formatValue
     * @return array<string, array<int, true>>
     */
    public static function findLeaderCells(
        array $rows,
        array $columns,
        callable $resolveValue,
        callable $formatValue,
    ): array {
        $rows = array_values($rows);
        $leaders = [];

        foreach ($columns as $column) {
            if ($column['format'] === 'starter') {
                continue;
            }

            $highestScore = null;
            $leaderRows = [];
            foreach ($rows as $rowIndex => $row) {
                $value = $resolveValue($row, $column);
                if (!is_numeric($value)) {
                    continue;
                }

                $formattedValue = $formatValue($value, $row, $column);
                $score = self::scoreFromValue($formattedValue);
                if ($score === null || $score <= 0) {
                    continue;
                }

                if ($highestScore === null || $score > $highestScore) {
                    $highestScore = $score;
                    $leaderRows = [$rowIndex => true];
                } elseif ($score === $highestScore) {
                    $leaderRows[$rowIndex] = true;
                }
            }

            if ($leaderRows !== []) {
                $leaders[$column['key']] = $leaderRows;
            }
        }

        return $leaders;
    }

    /**
     * Converts a formatted statistic into a numeric comparison value.
     *
     * @param mixed $value
     * @return float|null
     */
    private static function scoreFromValue(mixed $value): ?float
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $score = str_replace([',', '%'], '', (string)$value);
        if (!is_numeric($score)) {
            return null;
        }

        return (float)$score;
    }
}
