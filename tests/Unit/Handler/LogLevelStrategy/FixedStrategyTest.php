<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Unit\Handler\LogLevelStrategy;

use GuzzleLogMiddleware\Handler\ArrayHandler;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\FixedStrategy;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Log\LogLevel;

final class FixedStrategyTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     */
    public function fixedDebugLevelForAllValues($value)
    {
        $strategy = new FixedStrategy();
        $this->assertSame('debug', $strategy->getLevel($value, []));
    }

    public function valueProvider(): array
    {
        return [
            [new Request('get', 'www.test.com')],
            [new Response()],
            [new \Exception()],
            [new RequestException('Not Found', new Request('get', 'www.test.com'))],
        ];
    }

    /**
     * @test
     * @dataProvider statusCodeProvider
     * @param int $statusCode
     */
    public function executeRequestWithVariousResponseStatusCodesAndHttpErrorToFalse(int $statusCode)
    {
        $this->appendResponse($statusCode)
            ->createClient([
                RequestOptions::HTTP_ERRORS => false,
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::INFO, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::INFO, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
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
    public function logTransactionWhenRequestExceptionOccurs()
    {
        try {
            $this->mockHandler->append(
                new RequestException('Not Found', new Request('get', 'www.test.com'), new Response(404))
            );
            $this->createClient()->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::INFO, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::INFO, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
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
        $this->assertSame(LogLevel::INFO, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->records[1]['message']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        $strategy = new FixedStrategy(LogLevel::INFO, LogLevel::CRITICAL, LogLevel::DEBUG);
        return new LogMiddleware($this->logger, new ArrayHandler($strategy));
    }
}
