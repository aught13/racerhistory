<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BasketballBoxScoreCsvParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BasketballBoxScoreCsvParserTest extends TestCase
{
    /**
     * Test structured CSV player and totals rows normalize to importer data.
     *
     * @return void
     */
    public function testParsesStructuredCsv(): void
    {
        $csv = <<<'CSV'
row_type,side,jersey,name,MIN,FGM,FGA,TPM,TPA,FTM,FTA,ORB,DRB,RB,PF,FD,PTS,AST,TRN,STL,BS,BD
player,team,03,"CANAAN, Isaiah",33,5,12,3,7,6,7,1,3,4,3,,19,2,1,1,0,
totals,team,,,200,18,55,6,20,20,24,13,26,39,21,,62,11,12,10,3,
player,opponent,00,"SOKO, Ovie",31,5,6,0,0,0,0,2,7,9,3,,10,2,2,0,0,
totals,opponent,,,200,19,47,2,14,15,23,8,26,34,16,,55,7,15,3,7,
CSV;

        $result = (new BasketballBoxScoreCsvParser())->parse($csv);

        self::assertCount(2, $result['teams']);
        self::assertSame('CANAAN, Isaiah', $result['teams'][0]['players'][0]['name']);
        self::assertSame('33', $result['teams'][0]['players'][0]['MIN']);
        self::assertSame(62, $result['teams'][0]['totals']['PTS']);
        self::assertSame(55, $result['teams'][1]['score']);
    }

    /**
     * Test the downloadable template contains required columns and placeholders.
     *
     * @return void
     */
    public function testTemplateProvidesStructuredPlaceholders(): void
    {
        $template = (new BasketballBoxScoreCsvParser())->template();

        self::assertStringStartsWith('row_type,side,jersey,name,MIN', $template);
        self::assertStringContainsString('player,team', $template);
        self::assertStringContainsString('totals,opponent', $template);
        self::assertStringContainsString('period,field,value,PNT,OTO,SND,FB,BN,TIED,LC', $template);
        self::assertStringContainsString('period,team,,,,,,,,,,,,,,,,,,,,,1', $template);
        self::assertStringContainsString('game,game,,,,,,,,,,,,,,,,,,,,,,attendance', $template);
    }

    /**
     * Test empty template placeholders cannot be previewed as a valid import.
     *
     * @return void
     */
    public function testRejectsUnfilledTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one completed player, totals, period, or game row');

        $parser = new BasketballBoxScoreCsvParser();
        $parser->parse($parser->template());
    }

    /**
     * Test invalid enum values are reported before preview.
     *
     * @return void
     */
    public function testRejectsInvalidRowSide(): void
    {
        $csv = "row_type,side,jersey,name,MIN,FGM,FGA,TPM,TPA,FTM,FTA,ORB,DRB,RB,PF,FD,PTS,AST,TRN,STL,BS,BD\n"
            . "player,visitor,1,Player,10,1,1,0,0,0,0,0,0,0,0,,2,0,0,0,0,\n";

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('team or opponent as side');

        (new BasketballBoxScoreCsvParser())->parse($csv);
    }

    /**
     * Parse only the totals, period, and game-result rows supplied in a partial CSV.
     *
     * @return void
     */
    public function testParsesPartialCsvWithPeriodAndGameResults(): void
    {
        $headers = [
            'row_type', 'side', 'jersey', 'name', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA',
            'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL',
            'BS', 'BD', 'period', 'field', 'value', 'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
        ];
        $rows = [
            ['row_type' => 'totals', 'side' => 'team', 'PNT' => '16'],
            ['row_type' => 'period', 'side' => 'team', 'period' => '1', 'FGM' => '9', 'PTS' => '32'],
            ['row_type' => 'period', 'side' => 'opponent', 'period' => '1', 'PTS' => '51'],
            ['row_type' => 'game', 'side' => 'game', 'field' => 'attendance', 'value' => '1,957'],
            ['row_type' => 'game', 'side' => 'game', 'field' => 'official_2', 'value' => 'Referee B'],
            ['row_type' => 'game', 'side' => 'game', 'field' => 'period_2_team', 'value' => '10'],
        ];
        $stream = fopen('php://temp', 'r+');
        self::assertNotFalse($stream);
        fputcsv($stream, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_map(static fn(string $header): string => $row[$header] ?? '', $headers), ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($csv);

        $result = (new BasketballBoxScoreCsvParser())->parse($csv);

        self::assertSame(16, $result['teams'][0]['totals']['PNT']);
        self::assertSame('1957', $result['game_results']['attendance']);
        self::assertSame(['official_2' => 'Referee B'], $result['game_results']['officials']);
        self::assertSame(['team' => 32, 'opponent' => 51], $result['game_results']['period_scores']['1']);
        self::assertSame(['team' => 10], $result['game_results']['period_scores']['2']);
        self::assertSame(9, $result['period_boxes']['1']['team']['FGM']);
    }
}
