<?php
// tests/TestCase/Model/Table/GameEavTableEdgeTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GameEavTable;
use Cake\TestSuite\TestCase;

class GameEavTableEdgeTest extends TestCase
{
    protected array $fixtures = [
        'app.GameEav',
        'app.Games',
        'app.Sports',
    ];

    protected GameEavTable $GameEav;

    public function setUp(): void
    {
        parent::setUp();
        $this->GameEav = $this->getTableLocator()->get('GameEav');
    }

    public function testSetAttributeUpdateFailure(): void
    {
        // Simulate save failure by mocking Table
        $mock = $this->getMockBuilder(GameEavTable::class)
            ->onlyMethods(['save'])
            ->getMock();
        $mock->method('save')->willReturn(false);
        $result = $mock->setAttribute(1, 'fail_key', 'fail_value');
        $this->assertFalse($result);
    }

    public function testDeleteAttributeNotFound(): void
    {
        $result = $this->GameEav->deleteAttribute(9999, 'not_a_key');
        $this->assertFalse($result);
    }

    public function testGetEavTemplateForSportMissingConfig(): void
    {
        // Use a non-existent sportId
        $template = $this->GameEav->getEavTemplateForSport(9999, '2', '0');
        $this->assertIsArray($template);
        $this->assertArrayHasKey('period_1_team', $template); // Should fallback to default
    }

    public function testGetFormattedScoringMissingData(): void
    {
        $result = $this->GameEav->getFormattedScoring(9999, 2, 1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('periods', $result);
        $this->assertArrayHasKey('overtime', $result);
        $this->assertArrayHasKey('totals', $result);
    }
}
