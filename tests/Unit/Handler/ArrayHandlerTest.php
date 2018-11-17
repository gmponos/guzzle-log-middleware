<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\RequestOptions;
use Psr\Log\LogLevel;

final class ArrayHandlerTest extends AbstractLoggerMiddlewareTest
{
    protected function createMiddleware(): LoggerMiddleware
    {
        return new LoggerMiddleware($this->logger, new ArrayHandler());
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

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->history[0]['context']['request']['body']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->history[1]['context']['response']['body']);
    }

    /**
     * @test
     */
    public function logTransactionWithJsonResponse()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/json'], '{"status": true, "client": 13000}')
            ->createClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->history[1]['context']['response']['body']
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

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->history[1]['context']['response']['body']
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

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertStringEndsWith(' (truncated...)', $this->logger->history[1]['context']['response']['body']);
    }
}
