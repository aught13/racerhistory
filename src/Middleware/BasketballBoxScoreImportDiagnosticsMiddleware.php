<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Logs safe request diagnostics for basketball box-score imports.
 */
class BasketballBoxScoreImportDiagnosticsMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The next handler.
     * @return \Psr\Http\Message\ResponseInterface The response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isImporterPost($request)) {
            return $handler->handle($request);
        }

        $diagnostics = $this->requestDiagnostics($request);
        Log::debug('Basketball box-score import POST received: ' . $diagnostics);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $exception) {
            $exceptionMessage = str_replace(["\r", "\n"], ' ', $exception->getMessage());
            Log::warning(sprintf(
                'Basketball box-score import POST threw %s (code=%d): %s; %s',
                $exception::class,
                $exception->getCode(),
                $exceptionMessage,
                $diagnostics,
            ));

            throw $exception;
        }

        if ($response->getStatusCode() >= 400) {
            Log::warning(sprintf(
                'Basketball box-score import POST returned HTTP %d; %s',
                $response->getStatusCode(),
                $diagnostics,
            ));
        }

        return $response;
    }

    /**
     * Check whether this is the importer preview or save request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @return bool Whether the request targets the importer.
     */
    private function isImporterPost(ServerRequestInterface $request): bool
    {
        $path = rtrim($request->getUri()->getPath(), '/');

        return $request->getMethod() === 'POST'
            && preg_match('~(?:^|/)admin/basketball-box-score-import/index/\d+$~', $path) === 1;
    }

    /**
     * Build a value-free summary of the submitted form and relevant PHP limits.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @return string Safe diagnostics for the application log.
     */
    private function requestDiagnostics(ServerRequestInterface $request): string
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];
        $parsedInputValues = 0;
        array_walk_recursive($data, static function () use (&$parsedInputValues): void {
            $parsedInputValues++;
        });

        $periodFieldCounts = [];
        foreach (['period_boxes', 'period_boxes_selected'] as $fieldName) {
            $groups = $data[$fieldName] ?? [];
            if (!is_array($groups)) {
                continue;
            }
            foreach ($groups as $period => $fields) {
                $periodFieldCounts[] = sprintf(
                    '%s[%s]=%d',
                    $fieldName,
                    rawurlencode((string)$period),
                    is_array($fields) ? count($fields) : 0,
                );
            }
        }

        $token = $data['_Token'] ?? [];
        $tokenFieldCount = is_array($token) ? count($token) : 0;
        $intent = $data['intent'] ?? null;
        $intent = is_string($intent) && in_array($intent, ['preview', 'save'], true)
            ? $intent
            : 'other';
        $path = str_replace(["\r", "\n"], ['\\r', '\\n'], $request->getUri()->getPath());
        $contentType = trim(explode(';', $request->getHeaderLine('Content-Type'), 2)[0]);
        $contentType = str_replace(["\r", "\n"], '', $contentType);
        $contentLength = $request->getHeaderLine('Content-Length');
        $contentLength = str_replace(["\r", "\n"], '', $contentLength);
        $maxInputVars = ini_get('max_input_vars');
        $maxMultipartBodyParts = ini_get('max_multipart_body_parts');
        $postMaxSize = ini_get('post_max_size');

        return sprintf(
            'path=%s intent=%s content_type=%s content_length=%s parsed_input_values=%d top_level_fields=%d '
            . 'period_field_counts=%s token_field_count=%d max_input_vars=%s max_multipart_body_parts=%s post_max_size=%s',
            $path,
            $intent,
            $contentType !== '' ? $contentType : 'unknown',
            $contentLength !== '' ? $contentLength : 'unknown',
            $parsedInputValues,
            count($data),
            $periodFieldCounts !== [] ? implode(',', $periodFieldCounts) : 'none',
            $tokenFieldCount,
            $maxInputVars === false ? 'unavailable' : $maxInputVars,
            $maxMultipartBodyParts === false ? 'unavailable' : $maxMultipartBodyParts,
            $postMaxSize === false ? 'unavailable' : $postMaxSize,
        );
    }
}
