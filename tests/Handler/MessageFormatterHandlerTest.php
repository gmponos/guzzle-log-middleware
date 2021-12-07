<?php

namespace GuzzleLogMiddleware\Test\Handler;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\MessageFormatterInterface;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use GuzzleLogMiddleware\Handler\MessageFormatterHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \GuzzleLogMiddleware\Handler\MessageFormatterHandler
 */
class MessageFormatterHandlerTest extends TestCase
{
    /**
     * @covers \GuzzleLogMiddleware\Handler\MessageFormatterHandler::log()
     * @dataProvider successLogDataProvider
     */
    public function testCanLog(
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Throwable $expectedException,
        ?string $expectedLogMessage
    ): void {
        $formatter = $this->createMock(MessageFormatterInterface::class);
        $strategy = $this->createMock(LogLevelStrategyInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $loglevel = 'LOG_LEVEL';
        $formatter
            ->expects($this->once())
            ->method('format')
            ->with(
                $this->equalTo($request),
                $this->equalTo($response),
                $this->equalTo($expectedException)
            )
            ->willReturn($expectedLogMessage);
        $strategy
            ->expects($this->once())
            ->method('getLevel')
            ->with(
                $this->equalTo($expectedException ?? $response ?? $request)
            )
            ->willReturn($loglevel);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($loglevel),
                $this->equalTo($expectedLogMessage)
            );

        $handler = new MessageFormatterHandler($formatter, $strategy);
        $handler->log($logger, $request, $response, $expectedException);
    }

    public function successLogDataProvider(): \Generator
    {
        yield 'Response is not given.' => [
            // GIVEN request
            $this->mockRequest(true, true),
            // GIVEN response
            null,
            // GIVEN exception
            null,
            // EXPECTED logger message
            'Expected log message.'
        ];

        yield 'Response and Response are seekable.' => [
            // GIVEN request
            $this->mockRequest(true, true),
            // GIVEN response
            $this->mockResponse(true, true),
            // GIVEN exception
            null,
            // EXPECTED logger message
            'Expected log message.'
        ];

        yield 'Response and Response are seekable. Exception given.' => [
            // GIVEN request
            $this->mockRequest(true, true),
            // GIVEN response
            $this->mockResponse(true, true),
            // GIVEN exception
            new TransferException(),
            // EXPECTED logger message
            'Expected log message.'
        ];
    }

    /**
     * @covers \GuzzleLogMiddleware\Handler\MessageFormatterHandler::log()
     * @dataProvider cannotLogDataProvider
     */
    public function testCannotLog(
        RequestInterface $request,
        ?ResponseInterface $response
    ): void {
        $formatter = $this->createMock(MessageFormatterInterface::class);
        $strategy = $this->createMock(LogLevelStrategyInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $formatter->expects($this->never())->method('format');
        $strategy->expects($this->never())->method('getLevel');
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->equalTo('StringHandler can not log request/response because the body is not seekable/readable.')
            );

        $handler = new MessageFormatterHandler($formatter, $strategy);
        $handler->log($logger, $request, $response);
    }

    public function cannotLogDataProvider(): \Generator
    {
        yield 'Request is not seekable.' => [
            $this->mockRequest(false, true),
            $this->mockResponse(true, true),
        ];

        yield 'Request is not readable.' => [
            $this->mockRequest(true, false),
            $this->mockResponse(true, true),
        ];

        yield 'Response is not seekable.' => [
            $this->mockRequest(true, true),
            $this->mockResponse(false, true),
        ];

        yield 'Response is not readable.' => [
            $this->mockRequest(true, true),
            $this->mockResponse(true, false),
        ];
    }

    /**
     * @return MockObject|RequestInterface
     */
    private function mockRequest(bool $isSeekable, bool $isReadable)
    {
        $mock = $this->createMock(RequestInterface::class);
        $mock->method('getBody')->willReturn($this->mockStream($isSeekable, $isReadable));
        return $mock;
    }

    /**
     * @return MockObject|ResponseInterface
     */
    private function mockResponse(bool $isSeekable, bool $isReadable)
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('getBody')->willReturn($this->mockStream($isSeekable, $isReadable));
        return $mock;
    }

    /**
     * @return mixed|MockObject|StreamInterface
     */
    private function mockStream(bool $isSeekable, bool $isReadable)
    {
        $mock = $this->createMock(StreamInterface::class);
        $mock->method('isSeekable')->willReturn($isSeekable);
        $mock->method('isReadable')->willReturn($isReadable);
        return $mock;
    }
}
