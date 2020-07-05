<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler\LogLevelStrategy;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\StatusCodeStrategy;
use GuzzleLogMiddleware\Handler\MultiRecordArrayHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class StatusCodeStrategyTest extends AbstractLoggerMiddlewareTest
{
    protected function createMiddleware(): LogMiddleware
    {
        $strategy = new StatusCodeStrategy();
        $strategy->setLevel(300, LogLevel::WARNING);
        $strategy->setLevel(301, LogLevel::WARNING);
        $strategy->setLevel(400, LogLevel::WARNING);
        $strategy->setLevel(403, LogLevel::WARNING);
        $strategy->setLevel(500, LogLevel::WARNING);
        return new LogMiddleware($this->logger, new MultiRecordArrayHandler($strategy));
    }

    /**
     * @test
     */
    public function logTransactionWithNoCustomLevels(): void
    {
        $this->appendResponse(401)
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
     */
    public function logTransactionWhenTransferExceptionOccurs(): void
    {
        try {
            $this->mockHandler->append(new TransferException());
            $this->createClient()->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->records[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithCustomLevel(): void
    {
        $this->appendResponse(300)
            ->createClient([
                'log' => [
                    'levels' => [
                        300 => LogLevel::WARNING,
                        301 => LogLevel::WARNING,
                        402 => LogLevel::WARNING,
                        403 => LogLevel::WARNING,
                        500 => LogLevel::WARNING,
                    ],
                ],
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::WARNING, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
    }
}
