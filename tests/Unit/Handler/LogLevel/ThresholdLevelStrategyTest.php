<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler\LogLevel;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Handler\LogLevel\ThresholdLevelStrategy;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class ThresholdLevelStrategyTest extends AbstractLoggerMiddlewareTest
{
    protected function createMiddleware(): LoggerMiddleware
    {
        return new LoggerMiddleware($this->logger, new ArrayHandler(new ThresholdLevelStrategy()));
    }

    /**
     * @test
     */
    public function thresholdWorksTheSameOnFailedTransactions()
    {
        try {
            $this
                ->appendResponse(200)
                ->appendResponse(500);
            $client = $this->createClient([
                'log' => [
                    'on_exception_only' => true,
                ],
            ]);
            $client->get('/');
            $client->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith4xxCode()
    {
        try {
            $this->appendResponse(404)->createClient()->get('/');
        } catch (\Exception $e) {
            // The goal is not to assert the exception.
        }
        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::ERROR, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith5xxCode()
    {
        try {
            $this->appendResponse(500)->createClient()->get('/');
        } catch (\Exception $e) {
        }
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('critical', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }
}
