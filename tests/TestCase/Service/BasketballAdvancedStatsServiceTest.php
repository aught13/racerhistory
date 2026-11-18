<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\StatBasketGamePerson;
use App\Model\Entity\StatBasketSeasonPerson;
use App\Service\BasketballAdvancedStatsService;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;

class BasketballAdvancedStatsServiceTest extends TestCase
{
    private BasketballAdvancedStatsService $service;

    /**
     * Initialize the advanced stats calculator.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BasketballAdvancedStatsService();
    }

    /**
     * College basketball rates use the 0.475 free-throw possession multiplier.
     */
    public function testCalculatePlayerStatsUsesCollegeFormulas(): void
    {
        $player = [
            'GP' => '10',
            'MIN' => '250',
            'FGM' => '45',
            'FGA' => '90',
            'TPM' => '12',
            'TPA' => '30',
            'FTM' => '18',
            'FTA' => '24',
            'ORB' => '15',
            'DRB' => '35',
            'RB' => '50',
            'AST' => '22',
            'STL' => '10',
            'BS' => '5',
            'TRN' => '12',
            'PTS' => '120',
        ];
        $team = [
            'GP' => '10',
            'MIN' => '2000',
            'FGM' => '180',
            'FGA' => '380',
            'TPM' => '45',
            'TPA' => '120',
            'FTM' => '90',
            'FTA' => '130',
            'ORB' => '80',
            'DRB' => '140',
            'RB' => '220',
            'AST' => '95',
            'STL' => '50',
            'BS' => '25',
            'TRN' => '75',
            'PTS' => '495',
        ];
        $opponent = [
            'GP' => '10',
            'MIN' => '2000',
            'FGM' => '170',
            'FGA' => '360',
            'TPM' => '40',
            'TPA' => '110',
            'FTM' => '85',
            'FTA' => '120',
            'ORB' => '75',
            'DRB' => '130',
            'RB' => '205',
            'AST' => '88',
            'STL' => '45',
            'BS' => '22',
            'TRN' => '80',
            'PTS' => '465',
        ];

        $stats = $this->service->calculatePlayerStats($player, $team, $opponent, 400.0);
        $expected = [
            'PPG' => 12.0,
            '2PM' => 33.0,
            '2PA' => 60.0,
            'FG%' => 0.5,
            '2P%' => 0.55,
            '3P%' => 0.4,
            'FT%' => 0.75,
            'FT/FGA' => 0.2,
            'TS%' => 0.5917159763,
            'eFG%' => 0.5666666667,
            '3PAr' => 0.3333333333,
            'FTr' => 0.2666666667,
            'ORB%' => 0.1142857143,
            'DRB%' => 0.2604651163,
            'TRB%' => 0.1882352941,
            'AST%' => 0.3259259259,
            'STL%' => 0.0372634643,
            'BLK%' => 0.032,
            'TOV%' => 0.1058201058,
            'USG%' => 0.3511175617,
            'PProd' => 115.9255960348,
        ];

        foreach ($expected as $metric => $value) {
            $this->assertEqualsWithDelta($value, $stats[$metric], 0.00001, $metric);
        }
    }

    /**
     * Missing or zero denominators stay unavailable while valid per-game values remain.
     */
    public function testCalculatePlayerStatsHandlesUnavailableDenominators(): void
    {
        $stats = $this->service->calculatePlayerStats(
            ['GP' => '1', 'PTS' => '12', 'FGM' => '0', 'FGA' => '0', 'TPM' => '0', 'TPA' => '0', 'FTM' => '0', 'FTA' => '0', 'TRN' => '0'],
            [],
            [],
            null,
        );

        $this->assertSame(12.0, $stats['PPG']);
        $this->assertNull($stats['FG%']);
        $this->assertNull($stats['FT/FGA']);
        $this->assertNull($stats['TS%']);
        $this->assertNull($stats['3PAr']);
        $this->assertNull($stats['FTr']);
        $this->assertNull($stats['ORB%']);
        $this->assertNull($stats['AST%']);
        $this->assertNull($stats['STL%']);
        $this->assertNull($stats['BLK%']);
        $this->assertNull($stats['TOV%']);
        $this->assertNull($stats['USG%']);
        $this->assertNull($stats['PProd']);
    }

    /**
     * Player entities are enriched with per-game and possession-based values.
     */
    public function testEnrichPlayersUsesFortyMinuteFallbackAndDerivesRebounds(): void
    {
        $player = new StatBasketGamePerson([
            'GP' => '1',
            'MIN' => '30',
            'FGM' => '8',
            'FGA' => '15',
            'TPM' => '2',
            'TPA' => '5',
            'FTM' => '4',
            'FTA' => '5',
            'ORB' => '2',
            'DRB' => '6',
            'AST' => '5',
            'STL' => '2',
            'BS' => '1',
            'TRN' => '2',
            'PTS' => '22',
        ]);
        $team = [
            'GP' => '1',
            'FGM' => '28',
            'FGA' => '62',
            'TPM' => '8',
            'TPA' => '22',
            'FTM' => '14',
            'FTA' => '18',
            'ORB' => '10',
            'RB' => '35',
            'AST' => '16',
            'STL' => '8',
            'BS' => '4',
            'TRN' => '12',
            'PTS' => '78',
        ];
        $opponent = [
            'GP' => '1',
            'FGM' => '26',
            'FGA' => '58',
            'TPM' => '6',
            'TPA' => '18',
            'FTM' => '12',
            'FTA' => '16',
            'ORB' => '8',
            'RB' => '30',
            'AST' => '14',
            'STL' => '6',
            'BS' => '3',
            'TRN' => '15',
            'PTS' => '70',
        ];

        $this->service->enrichPlayers([$player], $team, $opponent);
        $advancedStats = $player->get('advanced_stats');

        $this->assertEqualsWithDelta(22.0, $advancedStats['PPG'], 0.001);
        $this->assertEqualsWithDelta(0.0833333333, $advancedStats['ORB%'], 0.00001);
        $this->assertEqualsWithDelta(0.1641025641, $advancedStats['TRB%'], 0.00001);
        $this->assertNotNull($advancedStats['PProd']);
    }

    /**
     * Team summaries contain shooting and per-game rates, not individual production.
     */
    public function testCalculateTeamSummary(): void
    {
        $team = [
            'GP' => '10',
            'MIN' => '2000',
            'FGM' => '180',
            'FGA' => '380',
            'TPM' => '45',
            'TPA' => '120',
            'FTM' => '90',
            'FTA' => '130',
            'ORB' => '80',
            'DRB' => '140',
            'RB' => '220',
            'AST' => '95',
            'STL' => '50',
            'BS' => '25',
            'TRN' => '75',
            'PTS' => '495',
        ];
        $opponent = ['ORB' => '75', 'DRB' => '130', 'RB' => '205'];

        $summary = $this->service->calculateTeamSummary($team, $opponent);

        $this->assertEqualsWithDelta(49.5, $summary['PPG'], 0.001);
        $this->assertEqualsWithDelta(0.5602716469, $summary['TS%'], 0.0001);
        $this->assertEqualsWithDelta(0.3157894737, $summary['3PAr'], 0.0001);
        $this->assertEqualsWithDelta(0.2368421053, $summary['FT/FGA'], 0.0001);
        $this->assertEqualsWithDelta(0.3809523810, $summary['ORB%'], 0.0001);
        $this->assertEqualsWithDelta(1.0, $summary['USG%'], 0.0001);
        $this->assertArrayNotHasKey('PProd', $summary);
    }

    /**
     * Season team minutes are total player-minutes and must be divided by five.
     */
    public function testEnrichPlayersUsesTotalTeamMinutesForSeasonRates(): void
    {
        $player = new StatBasketSeasonPerson([
            'GP' => '7',
            'MIN' => '172',
            'FGM' => '35',
            'FGA' => '82',
            'TPM' => '15',
            'TPA' => '33',
            'FTM' => '35',
            'FTA' => '37',
            'ORB' => '1',
            'DRB' => '10',
            'RB' => '11',
            'AST' => '16',
            'STL' => '9',
            'BS' => '2',
            'TRN' => '13',
            'PTS' => '120',
        ]);
        $team = [
            'GP' => '7',
            'MIN' => '1400',
            'FGM' => '183',
            'FGA' => '367',
            'TPM' => '70',
            'TPA' => '183',
            'FTM' => '117',
            'FTA' => '146',
            'ORB' => '70',
            'DRB' => '148',
            'RB' => '218',
            'AST' => '106',
            'STL' => '44',
            'BS' => '21',
            'TRN' => '64',
            'PTS' => '553',
        ];
        $opponent = [
            'FGA' => '380',
            'TPA' => '191',
            'FTA' => '177',
            'ORB' => '77',
            'DRB' => '123',
            'RB' => '200',
            'TRN' => '45',
            'PTS' => '521',
        ];

        $this->service->enrichPlayers([$player], $team, $opponent);
        $advancedStats = $player->get('advanced_stats');

        $this->assertEqualsWithDelta(0.2066801993, $advancedStats['AST%'], 0.00001);
        $this->assertEqualsWithDelta(0.3662668690, $advancedStats['USG%'], 0.00001);
        $this->assertEqualsWithDelta(108.7451095168, $advancedStats['PProd'], 0.00001);
    }

    /**
     * Invalid player collections fail explicitly rather than silently skipping rows.
     */
    public function testEnrichPlayersRejectsNonEntityRows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->enrichPlayers([new stdClass()], [], []);
    }
}
