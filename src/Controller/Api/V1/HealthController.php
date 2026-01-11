<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

class HealthController extends AppController
{
    /**
     * Basic health check endpoint.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $this->respond([
            'data' => [
                'status' => 'ok',
                'api_version' => 'v1',
            ],
        ]);
    }
}
