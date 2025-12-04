<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Sport;
use App\Model\Entity\SportConfig;
use App\Model\Entity\SportStatRegistry;
use Cake\TestSuite\TestCase;

/**
 * Sport Entity Test Case
 */
class SportEntityTest extends TestCase
{
    /**
     * Test getDisplayName returns sport name
     */
    public function testGetDisplayName(): void
    {
        $sport = new Sport(['sport_name' => 'Basketball']);
        $this->assertSame('Basketball', $sport->getDisplayName());
    }

    /**
     * Test getDisplayName returns default when no name
     */
    public function testGetDisplayNameDefault(): void
    {
        $sport = new Sport();
        $this->assertSame('Unknown Sport', $sport->getDisplayName());
    }

    /**
     * Test getSupportedPeriods with config
     */
    public function testGetSupportedPeriodsFromConfig(): void
    {
        $sport = new Sport();
        $sport->sport_configs = [
            new SportConfig(['config_key' => 'supports_periods', 'config_value' => '[2,4]']),
        ];
        $this->assertSame([2, 4], $sport->getSupportedPeriods());
    }

    /**
     * Test getSupportedPeriods returns defaults
     */
    public function testGetSupportedPeriodsDefault(): void
    {
        $sport = new Sport();
        $this->assertSame([2, 4], $sport->getSupportedPeriods());
    }

    /**
     * Test getDefaultPeriods from config
     */
    public function testGetDefaultPeriodsFromConfig(): void
    {
        $sport = new Sport();
        $sport->sport_configs = [
            new SportConfig(['config_key' => 'default_periods', 'config_value' => '2']),
        ];
        $this->assertSame(2, $sport->getDefaultPeriods());
    }

    /**
     * Test getDefaultPeriods returns default
     */
    public function testGetDefaultPeriodsDefault(): void
    {
        $sport = new Sport();
        $this->assertSame(4, $sport->getDefaultPeriods());
    }

    /**
     * Test getPeriodName from config
     */
    public function testGetPeriodNameFromConfig(): void
    {
        $sport = new Sport();
        $sport->sport_configs = [
            new SportConfig(['config_key' => 'period_name_2', 'config_value' => 'Half']),
        ];
        $this->assertSame('Half', $sport->getPeriodName(2));
    }

    /**
     * Test getPeriodName returns defaults
     */
    public function testGetPeriodNameDefaults(): void
    {
        $sport = new Sport();
        $this->assertSame('Half', $sport->getPeriodName(2));
        $this->assertSame('Quarter', $sport->getPeriodName(4));
        $this->assertSame('Inning', $sport->getPeriodName(9));
        $this->assertSame('Period', $sport->getPeriodName(5));
    }

    /**
     * Test getOfficials from config
     */
    public function testGetOfficialsFromConfig(): void
    {
        $sport = new Sport();
        $sport->sport_configs = [
            new SportConfig(['config_key' => 'officials', 'config_value' => '["Referee","Umpire","Scorer"]']),
        ];
        $this->assertSame(['Referee', 'Umpire', 'Scorer'], $sport->getOfficials());
    }

    /**
     * Test getOfficials returns defaults
     */
    public function testGetOfficialsDefault(): void
    {
        $sport = new Sport();
        $this->assertSame(['Referee', 'Umpire'], $sport->getOfficials());
    }

    /**
     * Test getScoringType from config
     */
    public function testGetScoringTypeFromConfig(): void
    {
        $sport = new Sport();
        $sport->sport_configs = [
            new SportConfig(['config_key' => 'scoring_type', 'config_value' => 'period']),
        ];
        $this->assertSame('period', $sport->getScoringType());
    }

    /**
     * Test getScoringType returns default
     */
    public function testGetScoringTypeDefault(): void
    {
        $sport = new Sport();
        $this->assertSame('cumulative', $sport->getScoringType());
    }

    /**
     * Test getStatTables without filters
     */
    public function testGetStatTablesAll(): void
    {
        $sport = new Sport();
        $sport->sport_stat_registry = [
            new SportStatRegistry(['context' => 'game', 'entity_type' => 'team']),
            new SportStatRegistry(['context' => 'season', 'entity_type' => 'player']),
        ];
        $this->assertCount(2, $sport->getStatTables());
    }

    /**
     * Test getStatTables with context filter
     */
    public function testGetStatTablesFilteredByContext(): void
    {
        $sport = new Sport();
        $sport->sport_stat_registry = [
            new SportStatRegistry(['context' => 'game', 'entity_type' => 'team']),
            new SportStatRegistry(['context' => 'season', 'entity_type' => 'player']),
        ];
        $result = $sport->getStatTables('game');
        $this->assertCount(1, $result);
        $this->assertSame('game', $result[0]->context);
    }

    /**
     * Test getStatTables with entity type filter
     */
    public function testGetStatTablesFilteredByEntityType(): void
    {
        $sport = new Sport();
        $sport->sport_stat_registry = [
            new SportStatRegistry(['context' => 'game', 'entity_type' => 'team']),
            new SportStatRegistry(['context' => 'season', 'entity_type' => 'player']),
        ];
        $result = $sport->getStatTables(null, 'player');
        $this->assertCount(1, $result);
        $this->assertSame('player', $result[0]->entity_type);
    }

    /**
     * Test getStatTable returns specific entry
     */
    public function testGetStatTable(): void
    {
        $sport = new Sport();
        $sport->sport_stat_registry = [
            new SportStatRegistry(['context' => 'game', 'entity_type' => 'team']),
            new SportStatRegistry(['context' => 'season', 'entity_type' => 'player']),
        ];
        $result = $sport->getStatTable('game', 'team');
        $this->assertNotNull($result);
        $this->assertSame('game', $result->context);
        $this->assertSame('team', $result->entity_type);
    }

    /**
     * Test getStatTable returns null when not found
     */
    public function testGetStatTableNotFound(): void
    {
        $sport = new Sport();
        $sport->sport_stat_registry = [];
        $this->assertNull($sport->getStatTable('game', 'team'));
    }
}
