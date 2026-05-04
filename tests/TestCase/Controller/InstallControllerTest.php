<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\InstallController
 */
class InstallControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Tests index accessible in debug mode.
     */
    public function testIndexAccessibleInDebugMode(): void
    {
        Configure::write('debug', true);

        $this->get('/install');
        $this->assertResponseOk();
        $this->assertResponseContains('Deployment Audit');
        $this->assertResponseContains('auditAccordion');
    }

    /**
     * Tests index shows audit categories.
     */
    public function testIndexShowsAuditCategories(): void
    {
        Configure::write('debug', true);

        $this->get('/install');
        $this->assertResponseOk();
        $this->assertResponseContains('PHP Version');
        $this->assertResponseContains('PHP Extensions');
        $this->assertResponseContains('Configuration');
        $this->assertResponseContains('Directory Permissions');
        $this->assertResponseContains('Security');
        $this->assertResponseContains('Frontend Assets');
    }

    /**
     * Tests index shows overall banner.
     */
    public function testIndexShowsOverallBanner(): void
    {
        Configure::write('debug', true);

        $this->get('/install');
        $this->assertResponseOk();
        // Should contain one of the overall status messages
        $body = (string)$this->_response->getBody();
        $hasStatus = str_contains($body, 'production ready')
            || str_contains($body, 'Warnings found')
            || str_contains($body, 'Errors found');
        $this->assertTrue($hasStatus, 'Expected an overall status banner in the response');
    }

    /**
     * Tests index blocked in production without token.
     */
    public function testIndexBlockedInProductionWithoutToken(): void
    {
        Configure::write('debug', false);
        // Ensure no INSTALL_TOKEN is set
        putenv('INSTALL_TOKEN');

        $this->get('/install');
        $this->assertResponseCode(404);
    }

    /**
     * Tests index blocked in production with wrong token.
     */
    public function testIndexBlockedInProductionWithWrongToken(): void
    {
        Configure::write('debug', false);
        putenv('INSTALL_TOKEN=correct-secret-token');

        $this->get('/install?token=wrong-token');
        $this->assertResponseCode(404);

        putenv('INSTALL_TOKEN');
    }

    /**
     * Tests index allowed in production with valid token.
     */
    public function testIndexAllowedInProductionWithValidToken(): void
    {
        Configure::write('debug', false);
        putenv('INSTALL_TOKEN=my-deploy-audit-token');

        $this->get('/install?token=my-deploy-audit-token');
        $this->assertResponseOk();
        $this->assertResponseContains('Deployment Audit');

        putenv('INSTALL_TOKEN');
    }

    /**
     * Tests uses install layout.
     */
    public function testUsesInstallLayout(): void
    {
        Configure::write('debug', true);

        $this->get('/install');
        $this->assertResponseOk();
        // The install layout is minimal — no Turbo, no nav
        $this->assertResponseContains('bootstrap@5.3.2');
        // Should NOT contain turbo or the default app nav
        $body = (string)$this->_response->getBody();
        $this->assertFalse(str_contains($body, 'turbo-refresh-method'), 'Install page should use minimal layout without Turbo');
    }

    /**
     * Tests contains cli hint.
     */
    public function testContainsCliHint(): void
    {
        Configure::write('debug', true);

        $this->get('/install');
        $this->assertResponseOk();
        $this->assertResponseContains('bin/deploy.sh');
    }
}
