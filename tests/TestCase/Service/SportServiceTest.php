<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportService;
use Cake\TestSuite\TestCase;

class SportServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Sports',
    ];

    private SportService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SportService();
    }

    public function testGetSportById(): void
    {
        $sport = $this->service->getSportById(1);
        $this->assertNotNull($sport);
        $this->assertSame(1, $sport->id);
    }

    public function testGetSportByIdReturnsNullForInvalidId(): void
    {
        $sport = $this->service->getSportById(99999);
        $this->assertNull($sport);
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
        $this->assertSame('Sport #99999', $label);
    }

    public function testGetAllSports(): void
    {
        $sports = $this->service->getAllSports();
        $this->assertIsArray($sports);
        $this->assertGreaterThan(0, count($sports));
    }

    public function testCreateSport(): void
    {
        $data = ['sport_name' => 'Test Sport'];
        $sport = $this->service->createSport($data);
        $this->assertNotFalse($sport);
        $this->assertSame('Test Sport', $sport->sport_name);
    }

    public function testUpdateSport(): void
    {
        $sport = $this->service->updateSport(1, ['sport_name' => 'Updated Sport']);
        $this->assertNotFalse($sport);
        $this->assertSame('Updated Sport', $sport->sport_name);
    }

    public function testDeleteSport(): void
    {
        $data = ['sport_name' => 'Delete Me'];
        $sport = $this->service->createSport($data);
        $this->assertNotFalse($sport);

        $result = $this->service->deleteSport($sport->id);
        $this->assertTrue($result);

        $deleted = $this->service->getSportById($sport->id);
        $this->assertNull($deleted);
    }

    public function testGetSportsForSelect(): void
    {
        $results = $this->service->getSportsForSelect();
        $this->assertIsArray($results);
        if (!empty($results)) {
            $first = $results[0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('label', $first);
        }
    }
}
