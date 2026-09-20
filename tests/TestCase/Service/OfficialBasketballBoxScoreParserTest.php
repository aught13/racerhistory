<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\OfficialBasketballBoxScoreParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OfficialBasketballBoxScoreParserTest extends TestCase
{
    /**
     * Test final player blocks, totals, labels, and date parsing.
     *
     * @return void
     */
    public function testParsesFinalPlayerBlocksAndTotals(): void
    {
        $text = <<<'TEXT'
3 Lamont Sams 17:47 1-2 0-1 0-0 0 2 2 2 0 2 0 2 0 0 0 -17
22 Daniel Mayfield 33:54 5-18 2-4 5-5 2 5 7 1 6 17 1 2 0 2 1 -42
Totals 19-60 4-15 18-21 15 27 42 23 17 60 7 23 4 5 3 -48
Major Fouls: Mzein (F1)
8 JJ Traynor 18:48 4-8 0-1 1-1 1 1 2 2 1 9 1 2 1 0 1 16
11 Dylan Anderson 17:22 1-3 0-2 2-2 3 4 7 3 1 4 1 0 0 1 0 10
Totals 38-79 15-43 17-25 19 27 46 17 23 108 27 5 11 3 5 48
Mississippi Val. - 60 Record: 1-2
Murray St. - 108 Record: 2-0
11/07/25 CFSB Center, Murray
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('11/07/25', $result['date']);
        $this->assertSame('Mississippi Val.', $result['teams'][0]['label']);
        $this->assertSame(60, $result['teams'][0]['score']);
        $this->assertSame('Daniel Mayfield', $result['teams'][0]['players'][1]['name']);
        $this->assertSame(5, $result['teams'][0]['players'][1]['FGM']);
        $this->assertSame(5, $result['teams'][0]['players'][1]['FTA']);
        $this->assertSame(42, $result['teams'][0]['totals']['RB']);
        $this->assertSame(108, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test invalid text is rejected.
     *
     * @return void
     */
    public function testRejectsTextWithoutTwoFinalBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OfficialBasketballBoxScoreParser())->parse('not a basketball box score');
    }

    /**
     * Test rows wrapped across physical PDF lines are recovered.
     *
     * @return void
     */
    public function testParsesWrappedPdfRows(): void
    {
        $text = <<<'TEXT'
8 JJ Traynor
24:09 4-6 0-0 0-0 0 3 3 2 0 8 2 2 0 1 0 -14
Totals 30-62 7-22 20-29 8 26 34
17 23 87 12 8 4 5 4 -3
Major Fouls: NONE
10 Torey Alston
35:36 12-21 0-2 2-3 4 12 16 2 2 26 1 3 0 2 1 12
Totals 37-74 6-16 10-18 17 29 46
23 17 90 17 10 3 4 5 3
Major Fouls: NONE
Murray State - 87 Record: 4-2
Middle Tennessee - 90 Record: 4-1
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertCount(2, $result['teams']);
        $this->assertSame('JJ Traynor', $result['teams'][0]['players'][0]['name']);
        $this->assertSame(90, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test later period tables do not replace the final box score.
     *
     * @return void
     */
    public function testIgnoresLaterPeriodTables(): void
    {
        $text = <<<'TEXT'
3 Lamont Sams 17:47 1-2 0-1 0-0 0 2 2 2 0 2 0 2 0 0 0 -17
Totals 19-60 4-15 18-21 15 27 42 23 17 60 7 23 4 5 3 -48
8 JJ Traynor 18:48 4-8 0-1 1-1 1 1 2 2 1 9 1 2 1 0 1 16
Totals 38-79 15-43 17-25 19 27 46 17 23 108 27 5 11 3 5 48
Mississippi Val. - 60 Record: 1-2
Murray St. - 108 Record: 2-0
Official Basketball Play by Play - First Half
Totals 9-33 1-8 4-5 10 16 26 11 6 23 3 14 2 2 1 -24
Totals 16-35 8-21 7-10 5 14 19 6 11 47 13 3 6 1 2 24
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame(60, $result['teams'][0]['totals']['PTS']);
        $this->assertSame(108, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test PDF-concatenated FTA and ORB columns are separated.
     *
     * @return void
     */
    public function testRepairsCompactFreeThrowColumns(): void
    {
        $text = <<<'TEXT'
8 JJ Traynor 18:48 4-8 0-1 1-11 1 2 2 1 9 1 2 1 0 1 16
Totals 4-8 0-1 1-1 1 1 2 2 1 9 1 2 1 0 1 16
10 KJ Tenner 21:13 4-6 1-2 1-21 2 3 2 3 10 6 3 2 0 1 21
Totals 4-6 1-2 1-2 1 2 3 2 3 10 6 3 2 0 1 21
A - 9 Record: 1
B - 10 Record: 2
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame(1, $result['teams'][0]['players'][0]['FTA']);
        $this->assertSame(1, $result['teams'][0]['players'][0]['ORB']);
        $this->assertSame(2, $result['teams'][1]['players'][0]['FTA']);
        $this->assertSame(1, $result['teams'][1]['players'][0]['ORB']);
    }

    /**
     * Test player names containing commas are parsed.
     *
     * @return void
     */
    public function testParsesCommaInPlayerName(): void
    {
        $text = <<<'TEXT'
7 Jermaine O'Neal, Jr. 14:55 1-5 0-1 0-0 1 2 3 2 0 2 1 2 0 0 0 0
Totals 1-5 0-1 0-0 1 2 3 2 0 2 1 2 0 0 0 0
8 JJ Traynor 18:48 4-8 0-1 1-1 1 1 2 2 1 9 1 2 1 0 1 16
Totals 4-8 0-1 1-1 1 1 2 2 1 9 1 2 1 0 1 16
A - 2 Record: 1
B - 9 Record: 2
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame("Jermaine O'Neal, Jr.", $result['teams'][0]['players'][0]['name']);
    }

    /**
     * Test the older NCAA Game Totals layout.
     *
     * @return void
     */
    public function testParsesLegacyGameTotalsFormat(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score -- Game Totals -- Final Statistics
Harris-Stowe vs Murray State
11/11/11 7:45 p.m. at Murray, Ky. (CFSB Center)
Harris-Stowe 49 • 3-1
## Player FG-FGA FG-FGA FT-FTA Off Def Tot PF TP A TO Blk Stl Min
22 KRAMER, Kevin f 2-5 1-3 1-2 2 2 4 2 6 0 2 0 2 27
50 LOVELESS, Jordan f 1-2 0-0 2-2 0 2 2 3 4 0 2 0 0 14
25 HOWARD, Lamarr 2-2 0-0 0-0 1120 40301 8
Totals 18-52 2-15 11-18 11 16 27 14 49 11 17 1 7 200
Murray State 76 • 1-0
## Player FG-FGA FG-FGA FT-FTA Off Def Tot PF TP A TO Blk Stl Min
02 DANIEL, Ed f 1-3 0-0 2-2 2 5 7 5 4 0 2 3 0 22
42 ASKA, Ivan f 8-11 0-0 0-0 6 3 9 1 16 2 2 0 0 25
Totals 30-62 5-16 11-15 18 25 43 18 76 19 16 3 9 200
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('11/11/11', $result['date']);
        $this->assertSame('Harris-Stowe', $result['teams'][0]['label']);
        $this->assertSame(49, $result['teams'][0]['score']);
        $this->assertSame('KRAMER, Kevin', $result['teams'][0]['players'][0]['name']);
        $this->assertSame('27', $result['teams'][0]['players'][0]['MIN']);
        $this->assertSame(6, $result['teams'][0]['players'][0]['PTS']);
        $this->assertSame(2, $result['teams'][0]['players'][0]['STL']);
        $this->assertSame('HOWARD, Lamarr', $result['teams'][0]['players'][2]['name']);
        $this->assertSame(3, $result['teams'][0]['players'][2]['TRN']);
        $this->assertSame('8', $result['teams'][0]['players'][2]['MIN']);
        $this->assertSame(3, $result['teams'][1]['players'][0]['BS']);
        $this->assertSame(76, $result['teams'][1]['totals']['PTS']);
    }
}
