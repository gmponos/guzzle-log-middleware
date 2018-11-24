<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Test\Unit\Middleware;

use Gmponos\GuzzleLogger\LogMiddleware;
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

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame('request_body', $this->logger->records[0]['context']['request']['body']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertSame('response_body', $this->logger->records[1]['context']['response']['body']);
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
        $this->assertCount(0, $this->logger->records);

        try {
            $client->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->logger->reset();

        $this->appendResponse(200);

        $client->get('/');
        $this->assertCount(0, $this->logger->records);
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

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
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

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
    }

    /**
     * @test
     * @dataProvider statusCodeProvider
     * @param int $statusCode
     */
    public function logTransactionWithStatistics(int $statusCode)
    {
        $this->appendResponse($statusCode)
            ->createClient([
                'log' => [
                    'statistics' => true,
                ],
                RequestOptions::HTTP_ERRORS => false,
            ])
            ->get('/');

        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP statistics', $this->logger->records[1]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[2]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[2]['message']);
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

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->records[1]['message']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger);
    }
}
