<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler\LogLevelStrategy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\ThresholdStrategy;
use GuzzleLogMiddleware\Handler\MultiRecordArrayHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class ThresholdStrategyTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     * @param string $expected
     */
    public function fixedDebugLevelForAllValues($value, string $expected)
    {
        $strategy = new ThresholdStrategy([
            ThresholdStrategy::INFORMATIONAL => LogLevel::DEBUG,
            ThresholdStrategy::SUCCESS => LogLevel::INFO,
            ThresholdStrategy::REDIRECTION => LogLevel::WARNING,
            ThresholdStrategy::CLIENT_ERRORS => LogLevel::ERROR,
            ThresholdStrategy::SERVER_ERRORS => LogLevel::EMERGENCY,
        ]);
        $this->assertSame($expected, $strategy->getLevel($value, []));
    }

    public function valueProvider(): array
    {
        return [
            [new Request('get', 'www.test.com'), LogLevel::DEBUG],
            [new Response(101), LogLevel::DEBUG],
            [new Response(200), LogLevel::INFO],
            [new Response(301), LogLevel::WARNING],
            [new Response(400), LogLevel::ERROR],
            [new Response(401), LogLevel::ERROR],
            [new Response(404), LogLevel::ERROR],
            [new Response(500), LogLevel::EMERGENCY],
            [new Response(503), LogLevel::EMERGENCY],
            [new \Exception(), LogLevel::CRITICAL],
            [new RequestException('Not Found', new Request('get', 'www.test.com')), LogLevel::CRITICAL],
        ];
    }

    /**
     * @test
     */
    public function strategyWorksCorrectlyWhenHttpErrorsIsSetToFalse()
    {
        $client = $this
            ->appendResponse(200)
            ->appendResponse(500)
            ->createClient([
                RequestOptions::HTTP_ERRORS => false,
            ]);

        $client->get('/');
        $client->get('/');

        $this->assertCount(4, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::INFO, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);

        $this->assertSame(LogLevel::DEBUG, $this->logger->records[2]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[2]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[3]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[3]['message']);
    }

    /**
     * @test
     */
    public function strategyWorksCorrectlyWhenExceptionsOnlyIsSetDuringRequest()
    {
        try {
            $this
                // this one MUST NOT be logged
                ->appendResponse(200)
                // this one MUST be logged
                ->appendResponse(500);
            $client = $this->createClient([
                'log' => [
                    'on_exception_only' => true,
                ],
            ]);
            $client->get('/');
            $client->get('/');
        } catch (\Exception $e) {
            // The goal is not to assert the exception.
        }

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
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
        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::ERROR, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith5xxCode()
    {
        try {
            $this->appendResponse(500)->createClient()->get('/');
        } catch (\Exception $e) {
            // The goal is not to assert the exception.
        }

        $this->assertCount(2, $this->logger->records);
        $this->assertSame('debug', $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame('critical', $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
    }

    /**
     * @test
     */
    public function logTwoTransactionOneWith4xxAndOneWith5xxCode()
    {
        $client = $this
            ->appendResponse(404)
            ->appendResponse(500)
            ->createClient();

        try {
            $client->get('/');
        } catch (\Exception $e) {
            // The goal is not to assert the exception.
        }

        try {
            $client->get('/');
        } catch (\Exception $e) {
            // The goal is not to assert the exception.
        }

        $this->assertCount(4, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::ERROR, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);

        $this->assertSame(LogLevel::DEBUG, $this->logger->records[2]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[2]['message']);
        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[3]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[3]['message']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, new MultiRecordArrayHandler(new ThresholdStrategy()));
    }
}
