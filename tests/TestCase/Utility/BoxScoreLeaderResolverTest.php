<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\BoxScoreLeaderResolver;
use Cake\TestSuite\TestCase;

class BoxScoreLeaderResolverTest extends TestCase
{
    /**
     * Leaders include ties at displayed precision and ignore unavailable, zero, and starter values.
     */
    public function testFindLeaderCellsIncludesTiesAndIgnoresNonLeaders(): void
    {
        $rows = [
            ['PTS' => 20, 'FG%' => 0.675, '3PAr' => 0.2451, 'TRB' => 3, 'GS' => 1, 'BLK' => 0, 'UN' => 1],
            ['PTS' => 20, 'FG%' => 0.6749, '3PAr' => 0.2454, 'TRB' => 5, 'GS' => 1, 'BLK' => null, 'UN' => 1],
            ['PTS' => 0, 'FG%' => null, '3PAr' => 0.0, 'TRB' => 0, 'GS' => 0, 'BLK' => 0, 'UN' => 1],
        ];
        $columns = [
            ['key' => 'PTS', 'format' => 'count'],
            ['key' => 'FG%', 'format' => 'percent'],
            ['key' => '3PAr', 'format' => 'ratio'],
            ['key' => 'TRB', 'format' => 'count'],
            ['key' => 'GS', 'format' => 'starter'],
            ['key' => 'BLK', 'format' => 'count'],
            ['key' => 'UN', 'format' => 'unavailable'],
        ];
        $resolveValue = static function (mixed $row, array $column): mixed {
            return is_array($row) ? ($row[$column['key']] ?? null) : null;
        };
        $formatValue = static function (mixed $value, mixed $row, array $column): string {
            return match ($column['format']) {
                'percent' => number_format((float)$value * 100, 1, '.', '') . '%',
                'ratio' => number_format((float)$value, 3, '.', ''),
                'unavailable' => '—',
                default => number_format((float)$value, 0, '.', ''),
            };
        };

        $this->assertSame([
            'PTS' => [0 => true, 1 => true],
            'FG%' => [0 => true, 1 => true],
            '3PAr' => [0 => true, 1 => true],
            'TRB' => [1 => true],
        ], BoxScoreLeaderResolver::findLeaderCells($rows, $columns, $resolveValue, $formatValue));
        $this->assertSame([], BoxScoreLeaderResolver::findLeaderCells([], $columns, $resolveValue, $formatValue));
    }

    /**
     * Team comparisons highlight the leading side, honoring lower-is-better metrics and ties.
     */
    public function testCompareValuesHighlightsPreferredSideAndTies(): void
    {
        $this->assertSame(
            ['left' => true, 'right' => false],
            BoxScoreLeaderResolver::compareValues(32, 26),
        );
        $this->assertSame(
            ['left' => false, 'right' => true],
            BoxScoreLeaderResolver::compareValues(26, 32),
        );
        $this->assertSame(
            ['left' => true, 'right' => false],
            BoxScoreLeaderResolver::compareValues('18.3%', '26.6%', true),
        );
        $this->assertSame(
            ['left' => false, 'right' => true],
            BoxScoreLeaderResolver::compareValues('26.6%', '18.3%', true),
        );
        $this->assertSame(
            ['left' => true, 'right' => true],
            BoxScoreLeaderResolver::compareValues('51.6%', '51.6%'),
        );
        $this->assertSame(
            ['left' => true, 'right' => false],
            BoxScoreLeaderResolver::compareValues(0, 15, true),
        );
        $this->assertSame(
            ['left' => false, 'right' => false],
            BoxScoreLeaderResolver::compareValues(0, 0),
        );
        $this->assertSame(
            ['left' => false, 'right' => false],
            BoxScoreLeaderResolver::compareValues(null, 15),
        );
        $this->assertSame(
            ['left' => false, 'right' => false],
            BoxScoreLeaderResolver::compareValues('-', '15'),
        );
    }
}
