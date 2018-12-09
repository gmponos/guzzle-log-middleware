<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler;

use GuzzleLogMiddleware\Handler\StringHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
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
    public function handlerWithValueAsRequest()
    {
        $this->handler->log($this->logger, new Request('get', \GuzzleHttp\Psr7\uri_for('www.test.com')));
        $this->assertCount(1, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertStringStartsWith('Guzzle HTTP request:', $this->logger->records[0]['message']);
        $this->assertCount(0, $this->logger->records[0]['context']);
    }

    /**
     * @test
     */
    public function handlerWithValueException()
    {
        $this->handler->log($this->logger, new \Exception());
        $this->assertCount(1, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->records[0]['message']);
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
        $this->assertCount(1, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP transfer time: 0.01 for uri: www.test.com', $this->logger->records[0]['message']);
    }

    /**
     * @test
     * @expectedException \GuzzleLogMiddleware\Handler\Exception\UnsupportedException
     */
    public function handlerWithValueThatDoesNotMatchMustThrowException()
    {
        $this->handler->log($this->logger, new \stdClass());
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, $this->handler);
    }
}
