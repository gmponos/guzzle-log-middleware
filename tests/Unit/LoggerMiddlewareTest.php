<?php

namespace Gmponos\GuzzleLogger\Test\Unit;

use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use Gmponos\GuzzleLogger\Test\TestApp\HistoryLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class LoggerMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var HistoryLogger
     */
    private $logger;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->mockHandler = new MockHandler();
        $this->logger = new HistoryLogger();
    }

    /**
     * @param int $code
     * @param array $headers
     * @param string $body
     * @param string $version
     * @param null $reason
     * @return $this
     */
    private function appendResponse($code = 200, array $headers = [], $body = '', $version = '1.1', $reason = null)
    {
        $this->mockHandler->append(new Response($code, $headers, $body, $version, $reason));
        return $this;
    }

    /**
     * @param array $options
     * @return Client
     */
    private function getClient($options = [])
    {
        $stack = HandlerStack::create($this->mockHandler);
        $stack->unshift(new LoggerMiddleware($this->logger));
        return new Client(
            array_merge([
                'handler' => $stack,
            ], $options)
        );
    }

    /**
     * @test
     */
    public function logSuccessfulTransaction()
    {
        $this->appendResponse(200, [], 'response_body')
            ->getClient()
            ->get('/', [RequestOptions::BODY => 'request_body']);

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('request_body', $this->logger->history[0]['context']['request']['body']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertSame('response_body', $this->logger->history[1]['context']['response']['body']);
    }

    /**
     * @test
     */
    public function doNotLogSuccessfulTransaction()
    {
        $this->appendResponse(200)
            ->getClient([
                'log' => [
                    'requests' => false,
                ],
            ])
            ->get('/');

        $this->assertCount(0, $this->logger->history);
    }

    /**
     * @test
     */
    public function logOnlyUnsuccessfulTransaction()
    {
        try {
            $this
                ->appendResponse(200)
                ->appendResponse(500);
            $client = $this->getClient([
                'log' => [
                    'requests' => false,
                ],
            ]);
            $client->get('/');
            $client->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('critical', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith4xxCode()
    {
        try {
            $this->appendResponse(404)->getClient()->get('/');
        } catch (RequestException $e) {
        }
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('error', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith5xxCode()
    {
        try {
            $this->appendResponse(500)->getClient()->get('/');
        } catch (RequestException $e) {
        }
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('critical', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith4xxCodeWithoutExceptions()
    {
        $this->appendResponse(404)->getClient(['exceptions' => false])->get('/');
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('error', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWith5xxCodeWithoutExceptions()
    {
        $this->appendResponse(500)->getClient(['exceptions' => false])->get('/');
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('critical', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithJsonResponse()
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $this->appendResponse(200, $headers, '{"status": true, "client": 13000}')
            ->getClient(['exceptions' => false])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertContains(
            [
                'status' => true,
                'client' => 13000,
            ],
            $this->logger->history[1]['context']['response']['body']
        );
    }

    /**
     * @test
     */
    public function logTransactionWithTransferException()
    {
        try {
            $this->mockHandler->append(new TransferException());

            $this->getClient()
                ->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('critical', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP exception', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function logTransactionChangedTheWarningThreshold()
    {
        try {
            $this->appendResponse(404)
                ->getClient([
                    'log' => [
                        'warning_threshold' => null,
                    ],
                ])
                ->get('/');
        } catch (\Exception $e) {
        }

        try {
            $this->appendResponse(500)
                ->getClient([
                    'log' => [
                        'warning_threshold' => null,
                    ],
                ])
                ->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(4, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);

        $this->assertSame('debug', $this->logger->history[2]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[2]['message']);
        $this->assertSame('critical', $this->logger->history[3]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[3]['message']);
    }

    /**
     * @test
     */
    public function logTransactionChangedTheErrorThreshold()
    {
        try {
            $this->appendResponse(404)
                ->getClient([
                    'log' => [
                        'warning_threshold' => null,
                        'error_threshold' => null,
                    ],
                ])
                ->get('/');
        } catch (\Exception $e) {
        }

        try {
            $this->appendResponse(500)
                ->getClient([
                    'log' => [
                        'warning_threshold' => null,
                        'error_threshold' => null,
                    ],
                ])
                ->get('/');
        } catch (\Exception $e) {
        }

        $this->assertCount(4, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);

        $this->assertSame('debug', $this->logger->history[2]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[2]['message']);
        $this->assertSame('debug', $this->logger->history[3]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[3]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithStatistics()
    {
        $this->appendResponse(200)
            ->getClient([
                'log' => [
                    'statistics' => true,
                ],
            ])
            ->get('/');

        $this->assertCount(3, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP statistics', $this->logger->history[1]['message']);
        $this->assertSame('debug', $this->logger->history[2]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[2]['message']);
    }

    /**
     * @test
     */
    public function logTransactionWithCustomLevel()
    {
        $this->appendResponse(300)
            ->getClient([
                'log' => [
                    'levels' => [
                        300 => 'warning',
                        301 => 'warning',
                        402 => 'warning',
                        403 => 'warning',
                        500 => 'warning',
                    ],
                ],
            ])
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('warning', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
    }

    /**
     * @test
     */
    public function doNotRequestOrResponseBodyBecauseOfSensitiveData()
    {
        $this->appendResponse(200, [], 'sensitive_data')
            ->getClient([
                'log' => [
                    'sensitive' => true,
                ],
            ])
            ->get('/', [RequestOptions::BODY => 'sensitive_request_data']);

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->history[0]['context']['request']['body']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertSame('Body contains sensitive information therefore it is not included.', $this->logger->history[1]['context']['response']['body']);
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
            ->getClient()
            ->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('debug', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP request', $this->logger->history[0]['message']);
        $this->assertSame('debug', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP response', $this->logger->history[1]['message']);
        $this->assertStringEndsWith(' (truncated...)', $this->logger->history[1]['context']['response']['body']);
    }
}
