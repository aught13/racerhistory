<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

/**
 * AuthTestTrait
 *
 * Helper for assigning an authenticated session user to integration test requests.
 *
 * NOTE: We intentionally use only the legacy 'Auth' session key here because
 * attempts to also inject request attributes / new Authentication session
 * structure caused intermittent FormProtection 500 errors in admin tests.
 * The Authentication plugin's Session authenticator (default key 'Auth') will
 * promote this array to an Identity automatically during the request cycle.
 * A future refactor can re-introduce attribute injection once stabilized.
 */
trait AuthTestTrait
{
    /**
     * Inject an authenticated identity into the next request.
     *
     * @param array $overrides Field overrides (id, username, role, email, status, etc.)
     * @return void
     */
    protected function mockIdentity(array $overrides = []): void
    {
        $defaults = [
            'id' => 1,
            'username' => 'admin',
            'role' => 'admin',
            'email' => 'admin@example.com',
            'status' => 'active',
        ];
        $data = $overrides + $defaults;

        $this->session(['Auth' => $data]);
    }
}
