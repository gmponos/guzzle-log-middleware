<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Test\Unit\Handler;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\LogMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Log\LogLevel;

final class ArrayHandlerTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @var ArrayHandler
     */
    private $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new ArrayHandler();
    }

    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     */
    public function handlerWorksNormalForAllPossibleValues($value)
    {
        $handler = new ArrayHandler();
        $handler->log($this->logger, $value, []);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->logger->reset();
    }

    public function valueProvider(): array
    {
        return [
            [new Request('get', 'www.test.com')],
            [new Response()],
            [new \Exception()],
            [new RequestException('Not Found', new Request('get', 'www.test.com'))],
            [new TransferStats(new Request('get', 'www.test.com'))],
        ];
    }

    /**
     * @test
     */
    public function doNotLogRequestOrResponseBodyBecauseOfSensitiveData()
    {
        $this->appendResponse(200, [], 'sensitive_data')
            ->createClient([
                'log' => [
                    'sensitive' => true,
                ],
            ])
            ->get('/', [RequestOptions::BODY => 'sensitive_request_data']);

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->records[0]['context']['request']['body']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->records[1]['context']['response']['body']);
    }

    /**
     * @test
     */
    public function logTransactionWithJsonResponse()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/json'], '{"status": true, "client": 13000}')
            ->createClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->records[1]['context']['response']['body']
        );
    }

    /**
     * @test
     */
    public function logTransactionWithJsonApiResponse()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/vnd.api+json'], '{"status": true, "client": 13000}')
            ->createClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->records[1]['context']['response']['body']
        );
    }

    /**
     * @test
     */
    public function logTransactionWithHugeResponseBody()
    {
        $body =
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..';

        $this->appendResponse(300, [], $body)
            ->createClient()
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertStringEndsWith(' (truncated...)', $this->logger->records[1]['context']['response']['body']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, $this->handler);
    }
}
