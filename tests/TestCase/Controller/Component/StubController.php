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

    /**
     * Runs the construct routine.
     *
     * @param ServerRequest|null $request
     */
    public function __construct(?ServerRequest $request = null)
    {
        parent::__construct($request);
        if ($request) {
            $this->request = $request;
        }
    }

    /**
     * Runs the set request routine.
     *
     * @param mixed $request
     */
    public function setRequest($request): self
    {
        $this->_request = $request;
        $this->request = $request;

        return $this;
    }

    /**
     * Runs the get request routine.
     */
    public function getRequest(): ServerRequest
    {
        return $this->_request ?? parent::getRequest();
    }

    /**
     * Runs the redirect routine.
     *
     * @param mixed $url
     * @param int $status
     * @param bool $exit
     */
    public function redirect($url, int $status = 302, bool $exit = true): Response
    {
        // Prevent actual routing during tests
        return new Response();
    }
}
