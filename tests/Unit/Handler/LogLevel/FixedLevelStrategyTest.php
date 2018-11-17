<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler\LogLevel;

use Gmponos\GuzzleLogger\Handler\ArrayHandler;
use Gmponos\GuzzleLogger\Handler\LogLevel\FixedLevelStrategy;
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\Unit\AbstractLoggerMiddlewareTest;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Log\LogLevel;

final class FixedLevelStrategyTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     */
    public function fixedDebugLevelForAllValues($value)
    {
        $strategy = new FixedLevelStrategy();
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

        $this->assertCount(2, $this->logger->history);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
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

    protected function createMiddleware(): LoggerMiddleware
    {
        return new LoggerMiddleware($this->logger, new ArrayHandler(new FixedLevelStrategy()));
    }
}
