<?php

namespace Gmponos\GuzzleLogger\Test\Unit;

use Gmponos\GuzzleLogger\Test\TestApp\HistoryLogger;
use GuzzleHttp\Handler\MockHandler;

class AbstractLoggerMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var HistoryLogger
     */
    protected $logger;

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
     * @param \Exception|null $reason
     * @return $this
     */
    protected function appendResponse(
        int $code = 200,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        ?\Exception $reason = null
    ) {
        $this->mockHandler->append(new Response($code, $headers, $body, $version, $reason));
        return $this;
    }
}
