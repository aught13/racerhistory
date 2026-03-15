<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\DeployAuditService;
use Cake\TestSuite\TestCase;

class DeployAuditServiceTest extends TestCase
{
    private DeployAuditService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new DeployAuditService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testRunReturnsExpectedStructure(): void
    {
        $audit = $this->service->run();

        $this->assertArrayHasKey('results', $audit);
        $this->assertArrayHasKey('errors', $audit);
        $this->assertArrayHasKey('warnings', $audit);
        $this->assertArrayHasKey('overall', $audit);
        $this->assertIsArray($audit['results']);
        $this->assertIsInt($audit['errors']);
        $this->assertIsInt($audit['warnings']);
        $this->assertContains($audit['overall'], ['pass', 'warn', 'fail']);
    }

    public function testResultItemsHaveRequiredKeys(): void
    {
        $audit = $this->service->run();

        foreach ($audit['results'] as $item) {
            $this->assertArrayHasKey('category', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('detail', $item);
            $this->assertContains($item['status'], ['ok', 'warn', 'fail']);
        }
    }

    public function testPhpVersionCheckPresent(): void
    {
        $audit = $this->service->run();

        $phpResults = array_filter($audit['results'], fn($r) => $r['category'] === 'PHP Version');
        $this->assertNotEmpty($phpResults);

        // Current environment is PHP 8.1+, should pass
        $first = array_values($phpResults)[0];
        $this->assertSame('ok', $first['status']);
        $this->assertStringContains('PHP', $first['label']);
    }

    public function testPhpExtensionsChecked(): void
    {
        $audit = $this->service->run();

        $extResults = array_filter($audit['results'], fn($r) => $r['category'] === 'PHP Extensions');
        $this->assertNotEmpty($extResults);

        // mbstring and intl should be present in the test environment
        $labels = array_map(fn($r) => $r['label'], array_values($extResults));
        $this->assertContains('ext-mbstring', $labels);
        $this->assertContains('ext-intl', $labels);
    }

    public function testConfigCategoryPresent(): void
    {
        $audit = $this->service->run();

        $configResults = array_filter($audit['results'], fn($r) => $r['category'] === 'Configuration');
        $this->assertNotEmpty($configResults);
    }

    public function testDirectoryPermissionsCategoryPresent(): void
    {
        $audit = $this->service->run();

        $dirResults = array_filter($audit['results'], fn($r) => $r['category'] === 'Directory Permissions');
        $this->assertNotEmpty($dirResults);
    }

    public function testSecurityCategoryPresent(): void
    {
        $audit = $this->service->run();

        $secResults = array_filter($audit['results'], fn($r) => $r['category'] === 'Security');
        $this->assertNotEmpty($secResults);
    }

    public function testAssetsCategoryPresent(): void
    {
        $audit = $this->service->run();

        $assetResults = array_filter($audit['results'], fn($r) => $r['category'] === 'Frontend Assets');
        $this->assertNotEmpty($assetResults);
    }

    public function testErrorAndWarningCountsMatchResults(): void
    {
        $audit = $this->service->run();

        $failCount = count(array_filter($audit['results'], fn($r) => $r['status'] === 'fail'));
        $warnCount = count(array_filter($audit['results'], fn($r) => $r['status'] === 'warn'));

        $this->assertSame($failCount, $audit['errors']);
        $this->assertSame($warnCount, $audit['warnings']);
    }

    public function testOverallPassWhenNoIssues(): void
    {
        // We can't control environment fully, but we can verify the logic:
        // If errors > 0, overall should be 'fail'
        // If only warnings, overall should be 'warn'
        // If neither, overall should be 'pass'
        $audit = $this->service->run();

        if ($audit['errors'] > 0) {
            $this->assertSame('fail', $audit['overall']);
        } elseif ($audit['warnings'] > 0) {
            $this->assertSame('warn', $audit['overall']);
        } else {
            $this->assertSame('pass', $audit['overall']);
        }
    }

    /**
     * Helper: PHPUnit 10-compatible string assertion.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
