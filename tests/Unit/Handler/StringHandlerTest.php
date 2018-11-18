<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Test\Unit\Handler;

use Gmponos\GuzzleLogger\Handler\StringHandler;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Psr\Log\LogLevel;

final class StringHandlerTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @var StringHandler
     */
    private $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new StringHandler();
    }

    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     */
    public function handlerWorksNormalForAllPossibleValues($value)
    {
        $this->handler->log($this->logger, $value);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->logger->clean();
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
    public function handlerWithValueAsRequest()
    {
        $this->handler->log($this->logger, new Request('get', \GuzzleHttp\Psr7\uri_for('www.test.com')));
        $this->assertCount(1, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertStringStartsWith('Guzzle HTTP request:', $this->logger->history[0]['message']);
        $this->assertCount(0, $this->logger->history[0]['context']);
    }

    /**
     * @test
     */
    public function handlerWithValueException()
    {
        $this->handler->log($this->logger, new \Exception());
        $this->assertCount(1, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->history[0]['message']);
    }

    /**
     * @test
     */
    public function handlerWithValueTransferStats()
    {
        $this->handler->log($this->logger, new TransferStats(
            new Request('get', 'www.test.com'),
            new Response(),
            0.01
        ));
        $this->assertCount(1, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP transfer time: 0.01 for uri: www.test.com', $this->logger->history[0]['message']);
    }

    /**
     * @test
     * @expectedException \Gmponos\GuzzleLogger\Handler\Exception\UnsupportedException
     */
    public function handlerWithValueThatDoesNotMatchMustThrowException()
    {
        $this->handler->log($this->logger, new \stdClass());
    }

    protected function createMiddleware(): LoggerMiddleware
    {
        return new LoggerMiddleware($this->logger, $this->handler);
    }
}
