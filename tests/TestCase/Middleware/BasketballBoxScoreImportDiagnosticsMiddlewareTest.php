<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\BasketballBoxScoreImportDiagnosticsMiddleware;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

final class BasketballBoxScoreImportDiagnosticsMemoryLogger extends AbstractLogger
{
    /**
     * @param list<string> $levels Levels to retain.
     */
    public function __construct(private array $levels)
    {
    }

    /**
     * @var list<array{level: string, message: string}>
     */
    public array $records = [];

    /**
     * @param mixed $level Log level.
     * @param string|\Stringable $message Log message.
     * @param array $context Log context.
     * @return void
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if (!in_array((string)$level, $this->levels, true)) {
            return;
        }

        $this->records[] = ['level' => (string)$level, 'message' => (string)$message];
    }
}

final class BasketballBoxScoreImportDiagnosticsResponseHandler implements RequestHandlerInterface
{
    public function __construct(private ResponseInterface $response)
    {
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @return \Psr\Http\Message\ResponseInterface The configured response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}

final class BasketballBoxScoreImportDiagnosticsThrowingHandler implements RequestHandlerInterface
{
    public function __construct(private Throwable $exception)
    {
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @return \Psr\Http\Message\ResponseInterface Never returns.
     * @throws \Throwable The configured exception.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw $this->exception;
    }
}

class BasketballBoxScoreImportDiagnosticsMiddlewareTest extends TestCase
{
    private array $originalDebugLogConfig;

    private array $originalErrorLogConfig;

    private BasketballBoxScoreImportDiagnosticsMemoryLogger $debugLogger;

    private BasketballBoxScoreImportDiagnosticsMemoryLogger $errorLogger;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $debugConfig = Log::getConfig('debug');
        $errorConfig = Log::getConfig('error');
        self::assertIsArray($debugConfig);
        self::assertIsArray($errorConfig);
        $this->originalDebugLogConfig = $debugConfig;
        $this->originalErrorLogConfig = $errorConfig;
        $this->debugLogger = new BasketballBoxScoreImportDiagnosticsMemoryLogger(['debug']);
        $this->errorLogger = new BasketballBoxScoreImportDiagnosticsMemoryLogger(['warning']);

        Log::drop('debug');
        Log::drop('error');
        Log::setConfig('debug', $this->debugLogger);
        Log::setConfig('error', $this->errorLogger);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Log::drop('debug');
        Log::drop('error');
        Log::setConfig('debug', $this->originalDebugLogConfig);
        Log::setConfig('error', $this->originalErrorLogConfig);

        parent::tearDown();
    }

    /**
     * Ensure successful import requests log field counts and PHP limits, not submitted values.
     *
     * @return void
     */
    public function testLogsSafeImportRequestDiagnostics(): void
    {
        $request = $this->importRequest([
            'intent' => 'save',
            '_Token' => ['fields' => 'sensitive-token'],
            'period_boxes' => ['team_1' => ['PTS' => 'sensitive-stat', 'FGM' => '12']],
            'period_boxes_selected' => ['team_1' => ['PTS' => '1']],
        ]);
        $response = new Response(['status' => 200]);
        $handler = new BasketballBoxScoreImportDiagnosticsResponseHandler($response);

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($request, $handler);

        self::assertSame($response, $result);
        self::assertCount(1, $this->debugLogger->records);
        self::assertSame([], $this->errorLogger->records);
        self::assertStringContainsString('intent=save', $this->debugLogger->records[0]['message']);
        self::assertStringContainsString('parsed_input_values=5', $this->debugLogger->records[0]['message']);
        self::assertStringContainsString('period_boxes[team_1]=2', $this->debugLogger->records[0]['message']);
        self::assertStringContainsString('period_boxes_selected[team_1]=1', $this->debugLogger->records[0]['message']);
        self::assertStringContainsString('max_input_vars=', $this->debugLogger->records[0]['message']);
        self::assertStringNotContainsString('sensitive-token', $this->debugLogger->records[0]['message']);
        self::assertStringNotContainsString('sensitive-stat', $this->debugLogger->records[0]['message']);
    }

    /**
     * Ensure requests outside the importer are passed through without logging.
     *
     * @return void
     */
    public function testDoesNotLogOtherRequests(): void
    {
        $request = $this->importRequest([], '/admin/games/add/1');
        $response = new Response(['status' => 200]);
        $handler = new BasketballBoxScoreImportDiagnosticsResponseHandler($response);

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($request, $handler);

        self::assertSame($response, $result);
        self::assertSame([], $this->debugLogger->records);
        self::assertSame([], $this->errorLogger->records);
    }

    /**
     * Ensure exceptions during import requests are logged and rethrown for normal error handling.
     *
     * @return void
     */
    public function testLogsAndRethrowsBadRequestExceptions(): void
    {
        $exception = new BadRequestException('Form token rejected');
        $handler = new BasketballBoxScoreImportDiagnosticsThrowingHandler($exception);

        try {
            (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($this->importRequest(), $handler);
            self::fail('Expected the bad request exception to be rethrown.');
        } catch (BadRequestException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertSame(['debug'], array_column($this->debugLogger->records, 'level'));
        self::assertSame(['warning'], array_column($this->errorLogger->records, 'level'));
        self::assertStringContainsString('BadRequestException', $this->errorLogger->records[0]['message']);
        self::assertStringContainsString('Form token rejected', $this->errorLogger->records[0]['message']);
    }

    /**
     * Ensure an error response returned without an exception is also logged.
     *
     * @return void
     */
    public function testLogsBadRequestResponses(): void
    {
        $response = new Response(['status' => 400]);
        $handler = new BasketballBoxScoreImportDiagnosticsResponseHandler($response);

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($this->importRequest(), $handler);

        self::assertSame($response, $result);
        self::assertSame(['debug'], array_column($this->debugLogger->records, 'level'));
        self::assertSame(['warning'], array_column($this->errorLogger->records, 'level'));
        self::assertStringContainsString('returned HTTP 400', $this->errorLogger->records[0]['message']);
    }

    /**
     * Create an import POST request for middleware tests.
     *
     * @param array<string, mixed> $data Parsed form data.
     * @param string $url Request path.
     * @return \Cake\Http\ServerRequest Import POST request.
     */
    private function importRequest(array $data = [], string $url = '/admin/basketball-box-score-import/index/1'): ServerRequest
    {
        return (new ServerRequest(['url' => $url]))
            ->withMethod('POST')
            ->withHeader('Content-Type', 'multipart/form-data; boundary=diagnostic-test')
            ->withHeader('Content-Length', '23456')
            ->withParsedBody($data);
    }
}
