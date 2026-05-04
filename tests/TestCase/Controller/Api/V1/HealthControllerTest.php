<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Api\V1\HealthController
 */
class HealthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/api/v1/health');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame('ok', $payload['data']['status'] ?? null);
        $this->assertSame('v1', $payload['data']['api_version'] ?? null);
    }
}
