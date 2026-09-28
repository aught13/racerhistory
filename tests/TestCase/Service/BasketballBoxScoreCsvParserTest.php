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
    }

    /**
     * Test empty template placeholders cannot be previewed as a valid import.
     *
     * @return void
     */
    public function testRejectsUnfilledTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one player row for team');

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
}
