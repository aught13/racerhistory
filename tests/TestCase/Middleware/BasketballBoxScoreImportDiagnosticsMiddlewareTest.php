<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\BasketballBoxScoreImportDiagnosticsMiddleware;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Stringable;

class BasketballBoxScoreImportDiagnosticsMiddlewareTest extends TestCase
{
    private array $originalDebugLogConfig;

    private array $originalErrorLogConfig;

    private LoggerInterface&MockObject $debugLogger;

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
        $this->debugLogger = $this->createMock(LoggerInterface::class);

        Log::drop('debug');
        Log::drop('error');
        Log::setConfig('debug', $this->debugLogger);
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
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($response);
        $message = '';
        $this->debugLogger->expects(self::once())->method('log')->with(
            'debug',
            $this->callback(static function (string|Stringable $loggedMessage) use (&$message): bool {
                $message = (string)$loggedMessage;

                return true;
            }),
            $this->anything(),
        );

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($request, $handler);

        self::assertSame($response, $result);
        self::assertStringContainsString('intent=save', $message);
        self::assertStringContainsString('parsed_input_values=5', $message);
        self::assertStringContainsString('period_boxes[team_1]=2', $message);
        self::assertStringContainsString('period_boxes_selected[team_1]=1', $message);
        self::assertStringContainsString('max_input_vars=', $message);
        self::assertStringNotContainsString('sensitive-token', $message);
        self::assertStringNotContainsString('sensitive-stat', $message);
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
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($response);
        $this->debugLogger->expects(self::never())->method('log');

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($request, $handler);

        self::assertSame($response, $result);
    }

    /**
     * Ensure exceptions during import requests are logged and rethrown for normal error handling.
     *
     * @return void
     */
    public function testLogsAndRethrowsBadRequestExceptions(): void
    {
        $exception = new BadRequestException('Form token rejected');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willThrowException($exception);
        $levels = [];
        $messages = [];
        $this->debugLogger->expects(self::exactly(2))->method('log')->willReturnCallback(
            static function (mixed $level, string|Stringable $message) use (&$levels, &$messages): void {
                $levels[] = (string)$level;
                $messages[] = (string)$message;
            },
        );

        try {
            (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($this->importRequest(), $handler);
            self::fail('Expected the bad request exception to be rethrown.');
        } catch (BadRequestException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertSame(['debug', 'warning'], $levels);
        self::assertStringContainsString('BadRequestException', $messages[1]);
        self::assertStringContainsString('Form token rejected', $messages[1]);
    }

    /**
     * Ensure an error response returned without an exception is also logged.
     *
     * @return void
     */
    public function testLogsBadRequestResponses(): void
    {
        $response = new Response(['status' => 400]);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($response);
        $levels = [];
        $messages = [];
        $this->debugLogger->expects(self::exactly(2))->method('log')->willReturnCallback(
            static function (mixed $level, string|Stringable $message) use (&$levels, &$messages): void {
                $levels[] = (string)$level;
                $messages[] = (string)$message;
            },
        );

        $result = (new BasketballBoxScoreImportDiagnosticsMiddleware())->process($this->importRequest(), $handler);

        self::assertSame($response, $result);
        self::assertSame(['debug', 'warning'], $levels);
        self::assertStringContainsString('returned HTTP 400', $messages[1]);
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
