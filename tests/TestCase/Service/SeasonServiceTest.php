<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SeasonService;
use Cake\TestSuite\TestCase;

class SeasonServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Seasons',
    ];

    private SeasonService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SeasonService();
    }

    public function testGetSeasonById(): void
    {
        $season = $this->service->getSeasonById(1);
        $this->assertNotNull($season);
        $this->assertSame(1, $season->id);
    }

    public function testGetSeasonByIdReturnsNullForInvalidId(): void
    {
        $season = $this->service->getSeasonById(99999);
        $this->assertNull($season);
    }

    public function testGetDisplayLabel(): void
    {
        $label = $this->service->getDisplayLabel(1);
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    public function testGetDisplayLabelFallbackForInvalidId(): void
    {
        $label = $this->service->getDisplayLabel(99999);
        $this->assertSame('Season #99999', $label);
    }

    public function testGetDisplayLabelFormatsStartEnd(): void
    {
        $data = ['start' => 2023, 'end' => 2024];
        $season = $this->service->createSeason($data);
        $this->assertNotFalse($season);

        $label = $this->service->getDisplayLabel($season->id);
        $this->assertSame('2023-2024', $label);
    }

    public function testGetDisplayLabelFormatsStartOnly(): void
    {
        $data = ['start' => 2025, 'end' => 2025];
        $season = $this->service->createSeason($data);
        $this->assertNotFalse($season);

        $label = $this->service->getDisplayLabel($season->id);
        $this->assertSame('2025', $label);
    }

    public function testGetAllSeasons(): void
    {
        $seasons = $this->service->getAllSeasons();
        $this->assertIsArray($seasons);
        $this->assertGreaterThan(0, count($seasons));
    }

    public function testGetAllSeasonsRespectsLimit(): void
    {
        $seasons = $this->service->getAllSeasons(2);
        $this->assertLessThanOrEqual(2, count($seasons));
    }

    public function testCreateSeason(): void
    {
        $data = ['start' => 2030, 'end' => 2031];
        $season = $this->service->createSeason($data);
        $this->assertNotFalse($season);
        $this->assertSame(2030, $season->start);
        $this->assertSame(2031, $season->end);
    }

    public function testUpdateSeason(): void
    {
        $season = $this->service->updateSeason(1, ['start' => 2099]);
        $this->assertNotFalse($season);
        $this->assertSame(2099, $season->start);
    }

    public function testDeleteSeason(): void
    {
        $data = ['start' => 2040, 'end' => 2041];
        $season = $this->service->createSeason($data);
        $this->assertNotFalse($season);

        $result = $this->service->deleteSeason($season->id);
        $this->assertTrue($result);

        $deleted = $this->service->getSeasonById($season->id);
        $this->assertNull($deleted);
    }

    public function testGetSeasonsForSelect(): void
    {
        $results = $this->service->getSeasonsForSelect();
        $this->assertIsArray($results);
        if (!empty($results)) {
            $first = $results[0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('label', $first);
        }
    }
}
