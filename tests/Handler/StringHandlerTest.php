<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler;

use GuzzleLogMiddleware\Handler\StringHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
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
     */
    public function handlerWillLogAllPossibleValues()
    {
        $this->handler->log($this->logger, $this->request, $this->response, $this->reason, $this->stats, []);
        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[2]['level']);
        $this->logger->reset();
    }

    /**
     * @test
     */
    public function handlerWillRecordRequest()
    {
        $this->handler->log($this->logger, $this->request, null, null, null, []);
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
        $this->handler->log($this->logger, $this->request, null, $this->reason, null, []);
        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertStringStartsWith('Guzzle HTTP exception: ', $this->logger->records[1]['message']);
    }

    /**
     * @test
     */
    public function handlerWithValueTransferStats()
    {
        $this->handler->log($this->logger, $this->request, $this->response, null, $this->stats, []);
        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP transfer time: 0.01 for uri: http://www.test.com/', $this->logger->records[1]['message']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, $this->handler);
    }
}
