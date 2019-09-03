<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test\Handler;

use GuzzleHttp\RequestOptions;
use GuzzleLogMiddleware\Handler\MultiRecordArrayHandler;
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleLogMiddleware\Test\AbstractLoggerMiddlewareTest;
use Psr\Log\LogLevel;

final class MultiRecordArrayHandlerTest extends AbstractLoggerMiddlewareTest
{
    /**
     * @var MultiRecordArrayHandler
     */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = new MultiRecordArrayHandler();
    }

    /**
     * @test
     */
    public function handlerWorksNormalForAllPossibleValues()
    {
        $handler = new MultiRecordArrayHandler();
        $handler->log($this->logger, $this->request, $this->response, $this->reason, $this->stats, []);
        $this->assertCount(3, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->logger->reset();
    }

    /**
     * @test
     */
    public function doNotLogRequestOrResponseBodyBecauseOfSensitiveData()
    {
        $this->appendResponse(200, [], 'sensitive_data')
            ->createClient([
                'log' => [
                    'sensitive' => true,
                ],
            ])
            ->get('/', [RequestOptions::BODY => 'sensitive_request_data']);

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->records[0]['context']['request']['body']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->records[1]['context']['response']['body']);
    }

    /**
     * @test
     */
    public function logTransactionWithJsonResponse()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/json'], '{"status": true, "client": 13000}')
            ->createClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->records[1]['context']['response']['body']
        );
    }

    /**
     * @test
     */
    public function logTransactionWithFormRequest()
    {
        $this
            ->appendResponse(200)
            ->createClient([
                RequestOptions::FORM_PARAMS => [
                    'one_param' => 'test',
                    'second_param' => 'test2',
                ],
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(
            [
                'one_param' => 'test',
                'second_param' => 'test2',
            ],
            $this->logger->records[0]['context']['request']['body']
        );
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertArrayNotHasKey('body', $this->logger->records[1]['context']['response']);
    }

    /**
     * @test
     */
    public function logTransactionWithJsonApiResponse()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/vnd.api+json'], '{"status": true, "client": 13000}')
            ->createClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->records[1]['context']['response']['body']
        );
    }

    /**
     * @test
     */
    public function logTransactionWithHugeResponseBody()
    {
        $body =
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..' .
            'Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..Very big body..';

        $this->appendResponse(300, [], $body)
            ->createClient()
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->records[0]['message']);
        $this->assertSame(LogLevel::DEBUG, $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->records[1]['message']);
        $this->assertStringEndsWith(' (truncated...)', $this->logger->records[1]['context']['response']['body']);
    }

    protected function createMiddleware(): LogMiddleware
    {
        return new LogMiddleware($this->logger, $this->handler);
    }
}
