<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Middleware;

use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LogLevel;

final class LoggerMiddlewareTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @test
     */
    public function logSuccessfulTransaction()
    {
        $this->appendResponse(200, [], 'response_body')
            ->createClient()
            ->get('/', [RequestOptions::BODY => 'request_body']);

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('request_body', $this->logger->history[0]['context']['request']['body']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertSame('response_body', $this->logger->history[1]['context']['response']['body']);
    }

    /**
     * @test
     */
    public function doNotLogOnSuccessfulTransactionWhenOnFailureOnlyIsTrue()
    {
        $this
            ->appendResponse(200)
            ->appendResponse(500);
        $client = $this->createClient([
            'log' => [
                'on_exception_only' => true,
            ],
        ]);
        $client->get('/');
        $this->assertCount(0, $this->logger->history);

        try {
            $client->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->logger->clean();

        $this->appendResponse(200);

        $client->get('/');
        $this->assertCount(0, $this->logger->history);
    }

    /**
     * @test
     * @dataProvider statusCodeProvider
     * @param int $statusCode
     */
    public function logTransactionWithWhenHttpErrorsIsFalse(int $statusCode)
    {
        $this->appendResponse($statusCode)
            ->createClient([
                RequestOptions::HTTP_ERRORS => false,
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     * @dataProvider statusCodeProvider
     * @param int $statusCode
     */
    public function logTransactionWithWhenHttpErrorsIsTrue(int $statusCode)
    {
        try {
            $this->appendResponse($statusCode)
                ->createClient([
                    RequestOptions::HTTP_ERRORS => true,
                ])
                ->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    public function statusCodeProvider()
    {
        return [
            [200],
            [201],
            [204],
            [400],
            [401],
            [404],
            [500],
            [503],
        ];
    }

    /**
     * @test
     */
    public function logTransactionWhenTransferExceptionOccurs()
    {
        try {
            $this->mockHandler->append(new TransferException());
            $this->createClient()->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithStatistics()
    {
        $this->appendResponse(200)
            ->createClient([
                'log' => [
                    'statistics' => true,
                ],
            ])
            ->get('/');

        $this->assertCount(3, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP statistics', $this->logger->history[1]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[2]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[2]['message']);
    }

    protected function createMiddleware(): LoggerMiddleware
    {
        return new LoggerMiddleware($this->logger);
    }
}
