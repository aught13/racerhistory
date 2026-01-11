<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PersonsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Persons',
    ];

    public function testIndexDefault(): void
    {
        $this->get('/api/v1/persons?limit=1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertCount(1, $payload['data']);
    }

    public function testIndexSearch(): void
    {
        $this->get('/api/v1/persons?q=John');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);

        $labels = array_map(fn($p) => (string)($p['label'] ?? ''), $payload['data']);
        $this->assertContains('John Doe', $labels);
    }

    public function testView(): void
    {
        $this->get('/api/v1/persons/1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['id'] ?? null);
        $this->assertSame('John Doe', $payload['data']['label'] ?? null);
    }

    public function testViewNotFound(): void
    {
        $this->get('/api/v1/persons/999');
        $this->assertResponseCode(404);
    }
}
