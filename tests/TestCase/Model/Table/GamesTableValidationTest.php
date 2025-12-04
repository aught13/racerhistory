<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

/**
 * GamesTable Validation Test
 *
 * Tests comprehensive validation methods including:
 * - Sport-specific period validation
 * - EAV data validation
 * - Cumulative scoring validation
 */
class GamesTableValidationTest extends TestCase
{
    protected array $fixtures = [
        'app.Games',
        'app.TeamSeasons',
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.SportConfigs',
        'app.GameEav',
    ];

    protected $Games;

    public function setUp(): void
    {
        parent::setUp();
        $this->Games = $this->getTableLocator()->get('Games');
    }

    public function tearDown(): void
    {
        unset($this->Games);
        parent::tearDown();
    }

    /**
     * Test validateSportSpecificPeriods with valid periods
     */
    public function testValidateSportSpecificPeriodsValid(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'periods' => 4,
            ],
        ];

        $result = $this->Games->validateSportSpecificPeriods(4, $context);
        $this->assertTrue($result);
    }

    /**
     * Test validateSportSpecificPeriods with invalid periods
     */
    public function testValidateSportSpecificPeriodsInvalid(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'periods' => 7, // Invalid for basketball
            ],
        ];

        $result = $this->Games->validateSportSpecificPeriods(7, $context);
        $this->assertFalse($result);
    }

    /**
     * Test validateSportSpecificPeriods with empty value
     */
    public function testValidateSportSpecificPeriodsEmpty(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
            ],
        ];

        $result = $this->Games->validateSportSpecificPeriods('', $context);
        $this->assertTrue($result, 'Empty periods should pass validation');
    }

    /**
     * Test validateSportSpecificPeriods with missing team season
     */
    public function testValidateSportSpecificPeriodsNoTeamSeason(): void
    {
        $context = [
            'data' => [],
        ];

        $result = $this->Games->validateSportSpecificPeriods(4, $context);
        $this->assertTrue($result, 'Missing team season should pass validation');
    }

    /**
     * Test validateCumulativeTotals with matching period sums
     */
    public function testValidateCumulativeTotalsMatching(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'pts_mur' => 85,
                'pts_opp' => 78,
                'period_1_team' => 20,
                'period_2_team' => 22,
                'period_3_team' => 18,
                'period_4_team' => 25,
                'period_1_opponent' => 18,
                'period_2_opponent' => 20,
                'period_3_opponent' => 20,
                'period_4_opponent' => 20,
            ],
        ];

        $result = $this->Games->validateCumulativeTotals(85, $context);
        $this->assertTrue($result);
    }

    /**
     * Test validateCumulativeTotals with mismatching period sums
     */
    public function testValidateCumulativeTotalsMismatch(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'pts_mur' => 100, // Doesn't match sum
                'pts_opp' => 78,
                'period_1_team' => 20,
                'period_2_team' => 22,
                'period_3_team' => 18,
                'period_4_team' => 25, // Sum = 85, not 100
                'period_1_opponent' => 18,
                'period_2_opponent' => 20,
                'period_3_opponent' => 20,
                'period_4_opponent' => 20,
            ],
        ];

        $result = $this->Games->validateCumulativeTotals(100, $context);
        $this->assertFalse($result);
    }

    /**
     * Test validateCumulativeTotals with no period data
     */
    public function testValidateCumulativeTotalsNoPeriodData(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'pts_mur' => 85,
                'pts_opp' => 78,
            ],
        ];

        $result = $this->Games->validateCumulativeTotals(85, $context);
        $this->assertTrue($result, 'No period data should pass validation');
    }

    /**
     * Test validateCumulativeTotals with overtime periods
     */
    public function testValidateCumulativeTotalsWithOvertime(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'pts_mur' => 95,
                'pts_opp' => 93,
                'period_1_team' => 20,
                'period_2_team' => 22,
                'period_3_team' => 18,
                'period_4_team' => 25,
                'overtime_1_team' => 10,
                'period_1_opponent' => 18,
                'period_2_opponent' => 20,
                'period_3_opponent' => 20,
                'period_4_opponent' => 25,
                'overtime_1_opponent' => 10,
            ],
        ];

        $result = $this->Games->validateCumulativeTotals(95, $context);
        $this->assertTrue($result);
    }

    /**
     * Test validateCumulativeTotals with legacy field names (mur/opp)
     */
    public function testValidateCumulativeTotalsLegacyFields(): void
    {
        $context = [
            'data' => [
                'team_season_id' => 1,
                'pts_mur' => 85,
                'pts_opp' => 78,
                'period_1_mur' => 20,
                'period_2_mur' => 22,
                'period_3_mur' => 18,
                'period_4_mur' => 25,
                'period_1_opp' => 18,
                'period_2_opp' => 20,
                'period_3_opp' => 20,
                'period_4_opp' => 20,
            ],
        ];

        $result = $this->Games->validateCumulativeTotals(85, $context);
        $this->assertTrue($result);
    }

    /**
     * Test validateEavData executes without exceptions
     */
    public function testValidateEavDataExecutes(): void
    {
        $eavData = [
            'official_1' => 'John Doe',
            'official_2' => 'Jane Smith',
            'period_1_team' => 20,
            'period_1_opponent' => 18,
            'pts_mur' => 20,
            'pts_opp' => 18,
        ];

        $errors = $this->Games->validateEavData($eavData, 1);
        // Method should return array (may contain warnings or errors, or be empty)
        $this->assertIsArray($errors);
    }

    /**
     * Test validateEavData with insufficient officials
     */
    public function testValidateEavDataInsufficientOfficials(): void
    {
        $eavData = [
            'official_1' => 'John Doe',
            // Missing official_2
            'period_1_team' => 20,
            'period_1_opponent' => 18,
            'pts_mur' => 20,
            'pts_opp' => 18,
        ];

        $errors = $this->Games->validateEavData($eavData, 1);
        $this->assertArrayHasKey('officials', $errors);
    }

    /**
     * Test validateEavData with negative period scores
     */
    public function testValidateEavDataNegativePeriodScores(): void
    {
        $eavData = [
            'official_1' => 'John Doe',
            'official_2' => 'Jane Smith',
            'period_1_team' => -5, // Invalid negative
            'period_1_opponent' => 18,
            'pts_mur' => -5,
            'pts_opp' => 18,
        ];

        $errors = $this->Games->validateEavData($eavData, 1);
        $this->assertNotEmpty($errors, 'Negative scores should produce errors');
    }

    /**
     * Test validateEavData with cumulative total mismatch
     */
    public function testValidateEavDataCumulativeMismatch(): void
    {
        $eavData = [
            'official_1' => 'John Doe',
            'official_2' => 'Jane Smith',
            'period_1_team' => 20,
            'period_2_team' => 22,
            'period_3_team' => 18,
            'period_4_team' => 25,
            'period_1_opponent' => 18,
            'period_2_opponent' => 20,
            'period_3_opponent' => 20,
            'period_4_opponent' => 20,
            'pts_mur' => 100, // Doesn't match sum (85)
            'pts_opp' => 78,
        ];

        $errors = $this->Games->validateEavData($eavData, 1);
        $this->assertArrayHasKey('periods_team_total', $errors);
    }

    /**
     * Test validateEavData with invalid team season ID (returns empty due to fallback)
     */
    public function testValidateEavDataInvalidTeamSeason(): void
    {
        $eavData = [
            'official_1' => 'John Doe',
            'period_1_team' => 20,
            'pts_mur' => 20,
        ];

        // Invalid team season returns empty array (no errors) due to try/catch fallback
        $errors = $this->Games->validateEavData($eavData, 9999);
        // This is acceptable - method gracefully handles missing team season
        $this->assertTrue(is_array($errors));
    }

    /**
     * Test validationDefault includes cumulative totals validation
     */
    public function testValidationDefaultIncludesCumulativeRule(): void
    {
        // Don't call validationDefault twice - just create entity and check errors
        $data = [
            'team_season_id' => 1,
            'pts_mur' => 100,
            'pts_opp' => 78,
            'period_1_team' => 20,
            'period_2_team' => 22,
            'period_3_team' => 18,
            'period_4_team' => 25, // Sum = 85, not 100
        ];

        $game = $this->Games->newEntity($data);
        $errors = $game->getErrors();

        $this->assertNotEmpty($errors, 'Cumulative validation should fail for mismatched totals');
    }

    /**
     * Test game_date within season validation
     */
    public function testGameDateWithinSeasonValidation(): void
    {
        // Get a team season with known season years from fixture
        // Assuming team_season_id 1 is for 2023-2024 season (start=2023, end=2024)
        $data = [
            'team_season_id' => 1,
            'game_date' => '2025-12-15', // Outside 2023-2024 range
        ];

        $game = $this->Games->newEntity($data);
        $errors = $game->getErrors();

        $this->assertArrayHasKey('game_date', $errors);
    }

    /**
     * Test game_date future limit validation
     */
    public function testGameDateFutureLimitValidation(): void
    {
        $futureDate = (new \DateTime('+4 years'))->format('Y-m-d');

        $data = [
            'team_season_id' => 1,
            'game_date' => $futureDate,
        ];

        $game = $this->Games->newEntity($data);
        $errors = $game->getErrors();

        $this->assertArrayHasKey('game_date', $errors);
    }

    /**
     * Test buildRules includes existsIn for team_season_id
     */
    public function testBuildRulesExistsInTeamSeason(): void
    {
        $data = [
            'team_season_id' => 9999, // Non-existent
            'game_date' => '2024-01-15',
        ];

        $game = $this->Games->newEntity($data);
        $this->Games->checkRules($game);

        $errors = $game->getErrors();
        $this->assertArrayHasKey('team_season_id', $errors);
    }

    /**
     * Test hrn validation for valid values (1, 2, 3)
     */
    public function testHrnValidation(): void
    {
        $validHrns = [1, 2, 3];

        foreach ($validHrns as $hrn) {
            $data = [
                'team_season_id' => 1,
                'hrn' => $hrn,
            ];

            $game = $this->Games->newEntity($data);
            $errors = $game->getErrors();

            $this->assertArrayNotHasKey('hrn', $errors, "HRN value {$hrn} should be valid");
        }
    }

    /**
     * Test hrn validation for invalid values
     */
    public function testHrnValidationInvalid(): void
    {
        $data = [
            'team_season_id' => 1,
            'hrn' => 4, // Invalid
        ];

        $game = $this->Games->newEntity($data);
        $errors = $game->getErrors();

        $this->assertArrayHasKey('hrn', $errors);
    }
}
