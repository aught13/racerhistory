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
     * Test the LiveStats format with integer minutes and combined ORB-DRB.
     *
     * @return void
     */
    public function testParsesIntegerMinutesAndCombinedReboundColumns(): void
    {
        $text = <<<'TEXT'
Bellarmine (4-5,0-0 ASUN) -vs- Murray St. (7-3,0-0 MVC)
12/06/25 at CFSB Center, Murray, KY
Bellarmine 68
Murray St. 81
# Player GS MIN FG 3PT FT ORB-DRB REB PF A TO BLK STL PTS
11 Waddell,Brian * 36 8-11 2-4 7-9 0-6 6 0 5 2 0 1 25
32 Karasinski,Jack * 34 8-12 2-4 3-5 1-3 4 1 1 2 0 2 21
Totals - 200 25-49 6-18 12-16 2-16 18 12 14 11 0 8 68
Murray St. 81
11 Traynor,JJ * 27 8-12 1-5 0-0 0-6 6 0 2 0 1 0 17
22 Jackson,Javon * 23 4-7 2-3 4-4 1-0 1 0 4 1 0 1 14
Totals - 200 30-57 11-30 10-11 10-25 35 13 18 13 3 4 81
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('Bellarmine', $result['teams'][0]['label']);
        $this->assertSame('Murray St.', $result['teams'][1]['label']);
        $this->assertSame('36', $result['teams'][0]['players'][0]['MIN']);
        $this->assertSame('1', $result['teams'][0]['players'][0]['GS']);
        $this->assertSame(0, $result['teams'][0]['players'][0]['ORB']);
        $this->assertSame(6, $result['teams'][0]['players'][0]['DRB']);
        $this->assertSame(25, $result['teams'][0]['players'][0]['PTS']);
        $this->assertSame('1', $result['teams'][0]['players'][0]['GS']);
        $this->assertSame(81, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test compact numeric tails emitted by Smalot PDF Parser.
     *
     * @return void
     */
    public function testParsesSmalotCompactedTraditionalRows(): void
    {
        $text = <<<'TEXT'
Bellarmine (4-5,0-0 ASUN) -vs- Murray St. (7-3,0-0 MVC)
12/06/25 at CFSB Center, Murray, KY
11Waddell,Brian * 368-11 2-4 7-9 0-6 6 0 5 2 0 125
Totals  -20025-49 6-18 12-16 2-16 18121411 0 868
Team Summary FG 3PT FT
08Traynor,JJ * 278-12 1-5 0-0 0-6 6 0 2 0 1 017
Totals  -20030-57 11-30 10-11 10-25 35131813 3 481
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame(68, $result['teams'][0]['totals']['PTS']);
        $this->assertSame(81, $result['teams'][1]['totals']['PTS']);
        $this->assertSame(25, $result['teams'][0]['players'][0]['PTS']);
        $this->assertSame(17, $result['teams'][1]['players'][0]['PTS']);
    }

    /**
     * Test column-oriented output from pdftotext without layout preservation.
     *
     * @return void
     */
    public function testParsesColumnarPdfText(): void
    {
        $text = <<<'TEXT'
Bellarmine (4-5,0-0 ASUN) -vs- Murray St. (7-3,0-0 MVC)
12/06/25 at CFSB Center, Murray, KY
Bellarmine 68
#
11
TM
Player
Waddell,Brian
TEAM
Totals
Team Summary
MIN
36
0
200
FG
8-11
0-0
25-49
3PT
2-4
0-0
6-18
FG
FT
7-9
0-0
12-16
ORB-DRB
0-6
1-1
2-16
REB
6
2
18
PF
0
0
12
A
5
0
14
TO BLK
2
0
0
0
11
0
STL
1
0
8
PTS
25
0
68
Murray St. 81
#
08
TM
Player
Traynor,JJ
TEAM
Totals
Team Summary
MIN
27
0
200
FG
8-12
0-0
30-57
3PT
1-5
0-0
11-30
FG
FT
0-0
0-0
10-11
ORB-DRB
0-6
1-1
10-25
REB
6
2
35
PF
0
0
13
A
2
0
18
TO BLK
0
0
3
0
4
0
STL
0
0
4
PTS
17
0
81
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame(68, $result['teams'][0]['score']);
        $this->assertSame(81, $result['teams'][1]['score']);
        $this->assertSame('Waddell,Brian', $result['teams'][0]['players'][0]['name']);
        $this->assertSame(25, $result['teams'][0]['players'][0]['PTS']);
        $this->assertSame(17, $result['teams'][1]['players'][0]['PTS']);
    }

    /**
     * Test the 2019 Official Box Score Game Totals format.
     *
     * @return void
     */
    public function testParsesFinalStatisticsFormat(): void
    {
        $text = <<<'TEXT'
Official Box Score
Southern U. vs Murray St.
Game Totals -- Final Statistics
November 09, 2019 at CFSB Center - Murray, Ky.
Southern U. 49
01 SHIVERS, AHSANTE G 5 2-8 1-6 0-0 0 2 2 3 0 0 0 1 27 -15
11 BLAKE, MONTESE G 13 5-9 1-2 2-3 0 1 1 1 1 2 0 1 18 -10
TEAM 1 1 2 0 1
TOTALS 49 19-61 3-22 8-11 9 19 28 28 6 14 8 9 200
Murray St. 69
01 SMITH, DAQUAN G 5 1-4 1-2 2-4 0 4 4 1 4 4 1 1 27 13
10 BROWN, TEVIN G 17 5-10 2-6 5-6 1 4 5 1 1 2 1 0 34 21
TEAM 1 2 3 0 0
TOTALS 69 22-48 3-16 22-32 10 35 45 16 13 21 5 5 200
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('November 09, 2019', $result['date']);
        $this->assertSame('Southern U.', $result['teams'][0]['label']);
        $this->assertSame(49, $result['teams'][0]['score']);
        $this->assertSame(69, $result['teams'][1]['score']);
        $this->assertSame('SHIVERS, AHSANTE', $result['teams'][0]['players'][0]['name']);
        $this->assertSame('1', $result['teams'][0]['players'][0]['GS']);
        $this->assertSame('27', $result['teams'][0]['players'][0]['MIN']);
        $this->assertSame(69, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test visitor/home final tables without a TOT-FG marker.
     *
     * @return void
     */
    public function testParsesVisitorHomeFinalTablesWithoutTotFgMarker(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score
VISITORS: Murray State 18-11 (13-5 OVC)
## Player Name FG-FGA FG-FGA FT-FTA OF DE TOT PF TP A TO BLK S MIN
13 MURRAY, Rod f 6-13 2-3 0-0 4 2 6 2 14 0 5 0 0 31
31 SPENCER, Isaac f 5-9 0-0 3-7 4 10 14 3 13 1 3 1 1 38
Totals.............. 25-55 5-12 13-21 18 28 46 16 68 8 24 4 6 200
HOME TEAM: Oklahoma 19-10 (12-6 Big 12)
## Player Name FG-FGA FG-FGA FT-FTA OF DE TOT PF TP A TO BLK S MIN
21 NAJERA, Eduardo f 8-15 1-1 3-3 4 3 7 3 20 2 2 0 2 37
24 HUMPHREY, Ryan f 7-14 0-0 0-0 2 3 5 5 14 0 2 1 2 34
Totals.............. 23-59 4-12 14-15 8 15 23 17 64 11 12 1 8 200
11/28/98 at Norman, Okla.
Official Basketball Box Score -- 1st Half
VISITORS: Murray State 4-0
Totals.............. 0-0 0-0 0-0 0 0 0 0 0 0 0 0 0
HOME TEAM: Oklahoma
Totals.............. 0-0 0-0 0-0 0 0 0 0 0 0 0 0 0
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('Murray State', $result['teams'][0]['label']);
        $this->assertSame(68, $result['teams'][0]['score']);
        $this->assertSame('Oklahoma', $result['teams'][1]['label']);
        $this->assertSame(64, $result['teams'][1]['score']);
        $this->assertSame('MURRAY, Rod', $result['teams'][0]['players'][0]['name']);
        $this->assertSame(64, $result['teams'][1]['totals']['PTS']);
    }

    /**
     * Test game results, scoring breakdown, and half totals from a legacy source.
     *
     * @return void
     */
    public function testParsesLegacyGameResultsScoringBreakdownAndPeriodTotals(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score -- Game Totals
VISITORS: Gardner-Webb 0-3
Totals.............. 24-74 8-26 20-26 20 23 43 16 76 16 18 3 7 200
HOME TEAM: Murray State 1-0
Totals.............. 41-77 8-21 13-21 19 35 54 19 103 26 14 10 12 200
Officials: Tim Gattis, Lee Cassell, John
Hampton
Attendance: 1,957
Score by Periods 1st 2nd Total
Gardner-Webb.................. 32 44 - 76
Murray State.................. 51 52 - 103
Points in the paint-GWU 8,MUR 46. Points off turnovers-GWU 15,MUR 18.
2nd chance points-GWU 26,MUR 18. Fast break points-GWU 0,MUR 2.
Bench points-GWU 23,MUR 17. Score tied-0 times. Lead changed-0 times.
Official Basketball Box Score -- 1st Half
VISITORS: Gardner-Webb
Totals.............. 9-32 5-13 9-11 6 12 18 8 32 9 11 1 5 100
HOME TEAM: Murray State
Totals.............. 20-35 6-15 5-8 6 19 25 8 51 14 11 6 8 100
Official Basketball Box Score -- 2nd Half
VISITORS: Gardner-Webb
Totals.............. 15-42 3-13 11-15 14 11 25 8 44 7 7 2 2 100
HOME TEAM: Murray State
Totals.............. 21-42 2-6 8-13 13 16 29 11 52 12 3 4 4 100
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('1957', $result['game_results']['attendance']);
        $this->assertSame(['Tim Gattis', 'Lee Cassell', 'John Hampton'], $result['game_results']['officials']);
        $this->assertSame(['team' => 32, 'opponent' => 51], $result['game_results']['period_scores']['1']);
        $this->assertSame(['team' => 44, 'opponent' => 52], $result['game_results']['period_scores']['2']);
        $this->assertSame(8, $result['teams'][0]['totals']['PNT']);
        $this->assertSame(18, $result['teams'][1]['totals']['OTO']);
        $this->assertSame(26, $result['teams'][0]['totals']['SND']);
        $this->assertSame(2, $result['teams'][1]['totals']['FB']);
        $this->assertSame(23, $result['teams'][0]['totals']['BN']);
        $this->assertSame(0, $result['teams'][1]['totals']['TIED']);
        $this->assertSame(0, $result['teams'][0]['totals']['LC']);
        $this->assertSame(9, $result['period_boxes']['1']['team']['FGM']);
        $this->assertSame(51, $result['period_boxes']['1']['opponent']['PTS']);
        $this->assertSame(44, $result['period_boxes']['2']['team']['PTS']);
    }

    /**
     * Test the 2021 Murray State athletics HTML text format.
     *
     * @return void
     */
    public function testParsesAthleticsHtmlBoxScoreFormat(): void
    {
        $text = <<<'TEXT'
Men's Basketball vs Cumberland (TN) on 11/09/21 - Box Score
Game Information
Cumberland (TN) - 77
#
Player
gs
min
fg
3pt
ft
orb-drb
reb
pf
a
to
blk
stl
pts
12
King, Tavon
*
26
6 - 11
3 - 7
4 - 6
0 - 1
1
4
1
1
0
0
19
Totals
200
23-56
11-27
20-31
6-18
24
23
7
PERIOD_SCORE_HEADERS|1st quarter1|2nd quarter2|3rd quarter3|Total
PERIOD_SCORE|Cumberland (TN)|32|30|15|77
PERIOD_SCORE|Murray St.|40|40|29|109
PERIOD_BOX|0|3rd Half|5-8|1-3|3-5
PERIOD_DETAIL|0|FG|1=12 - 27|2=15 - 33|3=32 - 68
PERIOD_DETAIL|0|PNT|1=12|2=20|3=8
PERIOD_DETAIL|0|RB|1=10|2=10|3=24
8
2
7
77
Murray St. - 109
#
Player
gs
min
fg
3pt
ft
orb-drb
reb
pf
a
to
blk
stl
pts
00
Williams, Kj
*
22
12 - 13
5 - 6
3 - 4
1 - 4
5
1
0
1
0
0
32
Totals
200
38-60
13-26
20-30
10-33
43
23
24
9
2
3
109
PERIOD_DETAIL|0|FG|1=16 - 34|2=7 - 22
PERIOD_DETAIL|0|PNT|1=14|2=2
PERIOD_DETAIL|1|FT|1=12 - 18|2=8 - 12
PERIOD_DETAIL|1|OTO|1=4|2=6
PERIOD_BOX|0|1st Half|16-34|6-14|7-10
PERIOD_BOX|1|2nd Half|21-33|4-11|8-12
PERIOD_SCORE_HEADERS|1st half1|2nd half2|Total
PERIOD_SCORE|Cumberland (TN)|45|32|77
PERIOD_SCORE|Murray St.|55|54|109
FINAL_DETAIL|0|PNT|16
FINAL_DETAIL|1|PNT|50
Site: Arena, ILReferees: Referee A, Referee B, Referee CView: Match History Attendance: 1,957
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('Cumberland (TN)', $result['teams'][0]['label']);
        $this->assertSame(77, $result['teams'][0]['score']);
        $this->assertSame(109, $result['teams'][1]['score']);
        $this->assertSame('King, Tavon', $result['teams'][0]['players'][0]['name']);
        $this->assertSame(32, $result['teams'][1]['players'][0]['PTS']);
        $this->assertSame(['team' => 15, 'opponent' => 29], $result['game_results']['period_scores']['3']);
        $this->assertArrayNotHasKey('4', $result['game_results']['period_scores']);
        $this->assertSame(15, $result['period_boxes']['3']['team']['PTS']);
        $this->assertSame(5, $result['period_boxes']['3']['team']['FGM']);
        $this->assertSame(8, $result['period_boxes']['3']['team']['PNT']);
        $this->assertSame(4, $result['period_boxes']['3']['team']['RB']);
        $this->assertSame(16, $result['teams'][0]['totals']['PNT']);
        $this->assertSame(50, $result['teams'][1]['totals']['PNT']);
        $this->assertSame(16, $result['period_boxes']['1']['team']['FGM']);
        $this->assertSame(34, $result['period_boxes']['1']['team']['FGA']);
        $this->assertSame(2, $result['period_boxes']['2']['team']['PNT']);
        $this->assertSame(8, $result['period_boxes']['2']['opponent']['FTM']);
        $this->assertSame(4, $result['period_boxes']['1']['opponent']['OTO']);
        $this->assertSame(7, $result['period_boxes']['1']['team']['FTM']);
        $this->assertSame(['team' => 45, 'opponent' => 55], $result['game_results']['period_scores']['1']);
        $this->assertSame('1957', $result['game_results']['attendance']);
        $this->assertSame(['Referee A', 'Referee B', 'Referee C'], $result['game_results']['officials']);
        $this->assertNotContains('Totals', array_column($result['teams'][0]['players'], 'name'));
        $this->assertNotContains('Totals', array_column($result['teams'][1]['players'], 'name'));
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

    /**
     * Test older visitor/home exports with dotted name and totals separators.
     *
     * @return void
     */
    public function testParsesLegacyVisitorHomeFormat(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score
VISITORS: Tennessee Temple 3-4
 TOT-FG 3-PT REBOUNDS
## Player Name FG-FGA FG-FGA FT-FTA OF DE TOT PF TP A TO BLK S MIN
11 MORRIS, Josh........ f 0-3 0-1 2-2 0 0 0 5 2 1 3 0 0 18
12 RESERVE, Player 0-0 0-0 0-0 0 0 0 0 0 0 0 0 0 4
Totals.............. 15-40 3-13 8-15 11 21 32 20 41 7 26 3 2 200
HOME TEAM: Murray State 3-0
 TOT-FG 3-PT REBOUNDS
## Player Name FG-FGA FG-FGA FT-FTA OF DE TOT PF TP A TO BLK S MIN
02 DANIEL, Ed.......... f 4-5 0-0 4-7 1 2 3 1 12 0 2 3 2 26
Totals.............. 28-57 10-23 17-23 12 19 31 16 83 19 9 5 17 200
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('Tennessee Temple', $result['teams'][0]['label']);
        $this->assertSame(41, $result['teams'][0]['score']);
        $this->assertSame('MORRIS, Josh', $result['teams'][0]['players'][0]['name']);
        $this->assertSame('1', $result['teams'][0]['players'][0]['GS']);
        $this->assertNull($result['teams'][0]['players'][1]['GS']);
        $this->assertSame('Murray State', $result['teams'][1]['label']);
        $this->assertSame(83, $result['teams'][1]['score']);
    }

    /**
     * Test compacted legacy stat columns and an OCR-damaged minutes value.
     *
     * @return void
     */
    public function testRepairsCompactedLegacyColumnsAndTrailingMinutesDash(): void
    {
        $text = <<<'TEXT'
Official Basketball Box Score -- Game Totals -- Final Statistics
Murray State vs Southern Mississippi
11/26/11 8 pm at Anchorage, Alaska
Murray State 90 • 7-0
## Player FG-FGA FG-FGA FT-FTA Off Def Tot PF TP A TO Blk Stl Min
02 DANIEL, Ed f 2-4 0-0 4-7 4 5 9 3 8 0 0 3 0 31
Totals 31-62 10-21 18-25 15 26 41 21 90 15 18 7 8 250
Southern Mississippi 81 • 4-2
## Player FG-FGA FG-FGA FT-FTA Off Def Tot PF TP A TO Blk Stl Min
10 Jenkins,Cedric 0-1 0-0 0-0 0002 00000 3
24 Mills,Jonathan 4-8 0-0 4-5 6 2 8 4 12 0 0 0 0 40-
Totals 25-67 7-22 24-27 16 20 36 21 81 6 15 2 9 250
TEXT;

        $result = (new OfficialBasketballBoxScoreParser())->parse($text);

        $this->assertSame('Jenkins,Cedric', $result['teams'][1]['players'][0]['name']);
        $this->assertSame(2, $result['teams'][1]['players'][0]['PF']);
        $this->assertSame('3', $result['teams'][1]['players'][0]['MIN']);
        $this->assertSame('40', $result['teams'][1]['players'][1]['MIN']);
    }
}
