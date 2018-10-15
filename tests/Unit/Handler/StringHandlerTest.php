<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler;

use Gmponos\GuzzleLogger\Handler\HandlerInterface;
use Gmponos\GuzzleLogger\Handler\StringHandler;
use Gmponos\GuzzleLogger\Test\TestApp\HistoryLogger;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Psr\Log\LogLevel;

final class StringHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var HistoryLogger
     */
    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->logger = new HistoryLogger();
        $this->handler = new StringHandler();
    }

    /**
     * @test
     */
    public function handlerWithValueException()
    {
        $this->handler->log($this->logger, new \Exception());
        $this->assertCount(1, $this->logger->history);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->history[0]['level']);
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
}