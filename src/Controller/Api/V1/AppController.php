<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController as BaseAppController;

/**
 * AppController
 *
 * Base controller for API v1 controllers.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class AppController extends BaseAppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->disableAutoLayout();
    }

    /**
     * Runs the respond routine.
     *
     * @param array $payload Data to be serialized in the response.
     * @param int $status HTTP status code.
     */
    protected function respond(array $payload, int $status = 200): void
    {
        $this->set($payload);
        $this->viewBuilder()->setOption('serialize', array_keys($payload));
        $this->setResponse(
            $this->getResponse()
                ->withType('application/json')
                ->withStatus($status),
        );
    }

    /**
     * @param string $message Error message.
     * @param int $status HTTP status code.
     * @param array<string,mixed> $details Extra error details.
     */
    protected function respondError(string $message, int $status = 400, array $details = []): void
    {
        $error = ['message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        $this->respond(['error' => $error], $status);
    }

    /**
     * Safely read an integer query parameter.
     *
     * @param string $key Query parameter name.
     * @param int|null $default Default value when the parameter is absent or invalid.
     */
    protected function getIntQuery(string $key, ?int $default = null): ?int
    {
        $raw = (string)$this->getRequest()->getQuery($key, '');
        if ($raw == '') {
            return $default;
        }

        if (!ctype_digit($raw)) {
            return $default;
        }

        return (int)$raw;
    }

    /**
     * Read and clamp a "limit" query parameter.
     *
     * @param int $default Default limit.
     * @param int $max Maximum allowed limit.
     */
    protected function getLimit(int $default = 50, int $max = 200): int
    {
        $limit = $this->getIntQuery('limit', $default) ?? $default;
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > $max) {
            $limit = $max;
        }

        return $limit;
    }
}
