<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler\LogLevel;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Handler\LogLevel\StatusCodeStrategy;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class StatusCodeStrategyTest extends AbstractLoggerMiddlewareTest
{
    protected function createMiddleware(): LoggerMiddleware
    {
        $strategy = new StatusCodeStrategy();
        $strategy->setLevel(300, LogLevel::WARNING);
        $strategy->setLevel(301, LogLevel::WARNING);
        $strategy->setLevel(402, LogLevel::WARNING);
        $strategy->setLevel(403, LogLevel::WARNING);
        $strategy->setLevel(500, LogLevel::WARNING);
        return new LoggerMiddleware($this->logger, new ArrayHandler($strategy));
    }

    /**
     * @test
     */
    public function logTransactionWithCustomLevel()
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

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::WARNING, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }
}
