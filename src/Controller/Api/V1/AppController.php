<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController as BaseAppController;

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
     * @param array<string,mixed> $payload
     */
    protected function respond(array $payload, int $status = 200): void
    {
        $this->set($payload);
        $this->viewBuilder()->setOption('serialize', array_keys($payload));
        $this->response = $this->response
            ->withType('application/json')
            ->withStatus($status);
    }

    /**
     * @param array<string,mixed> $details
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
