<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Lightweight stub controller for UserManagerComponent tests.
 */
class StubController extends Controller
{
    /**
     * Users table reference.
     *
     * @var mixed
     */
    public $Users;
    /**
     * Flash component instance.
     *
     * @var mixed
     */
    public $Flash;
    /**
     * Authentication component mock.
     *
     * @var mixed
     */
    public $Authentication;

    protected ?ServerRequest $_request = null;

    public function __construct(?ServerRequest $request = null)
    {
        parent::__construct($request);
        if ($request) {
            $this->request = $request;
        }
    }

    public function setRequest($request): self
    {
        $this->_request = $request;
        $this->request = $request;

        return $this;
    }

    public function getRequest(): ServerRequest
    {
        return $this->_request ?? parent::getRequest();
    }

    public function redirect($url, int $status = 302, bool $exit = true): Response
    {
        // Prevent actual routing during tests
        return new Response();
    }
}
