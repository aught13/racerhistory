<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * DebugSessionMiddleware
 *
 * Captures the initial session `Auth` value and attaches it to the
 * request attributes and response header for troubleshooting tests.
 */
class DebugSessionMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');
        if (!$session instanceof Session) {
            $session = null;
        }

        $auth = null;
        if ($session !== null) {
            $auth = $session->read('Auth');
        }

        // Attach the captured auth to the request for later inspection.
        $request = $request->withAttribute('debug.initialAuth', $auth);

        $response = $handler->handle($request);
        // Inspect session after request handling to see if the Auth value changed.
        $final = null;
        $initialSessionObj = null;
        $finalSessionObj = null;
        $initialSessionId = null;
        if ($session !== null) {
            $final = $session->read('Auth');
            $initialSessionObj = function_exists('spl_object_id') ? spl_object_id($session) : null;
            try {
                $initialSessionId = $session->id();
            } catch (Throwable $e) {
                $initialSessionId = null;
            }
        }

        // Add short JSON headers for easy inspection in tests.
        if ($auth !== null) {
            $json = json_encode($auth, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                $response = $response->withHeader('X-Debug-Initial-Auth', $json);
            }
        }
        if ($final !== null) {
            $jsonF = json_encode($final, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonF !== false) {
                $response = $response->withHeader('X-Debug-Final-Auth', $jsonF);
            }
        }

        if ($initialSessionObj !== null) {
            $response = $response->withHeader('X-Debug-Session-Obj-Initial', (string)$initialSessionObj);
        }
        if ($final !== null && function_exists('spl_object_id')) {
            $finalSessionObj = spl_object_id($session);
            $response = $response->withHeader('X-Debug-Session-Obj-Final', (string)$finalSessionObj);
        }
        if ($initialSessionId !== null) {
            $response = $response->withHeader('X-Debug-Session-Id-Initial', (string)$initialSessionId);
        }
        if ($final !== null && isset($initialSessionId)) {
            $response = $response->withHeader('X-Debug-Session-Id-Final', (string)$session->id());
        }

        return $response;
    }
}
