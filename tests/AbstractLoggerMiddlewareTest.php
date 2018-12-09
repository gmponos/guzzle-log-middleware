<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleLogMiddleware\LogMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

abstract class AbstractLoggerMiddlewareTest extends TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var TestLogger
     */
    protected $logger;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->mockHandler = new MockHandler();
        $this->logger = new TestLogger();
    }

    /**
     * @param int $code
     * @param array $headers
     * @param string $body
     * @param string $version
     * @param string|null $reason
     * @return $this
     */
    protected function appendResponse(
        int $code = 200,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        string $reason = null
    ) {
        $this->mockHandler->append(new Response($code, $headers, $body, $version, $reason));
        return $this;
    }

    /**
     * @param array $options
     * @return Client
     */
    protected function createClient(array $options = [])
    {
        $stack = HandlerStack::create($this->mockHandler);
        $stack->unshift($this->createMiddleware());
        return new Client(
            array_merge([
                'handler' => $stack,
            ], $options)
        );
    }

    abstract protected function createMiddleware(): LogMiddleware;
}
