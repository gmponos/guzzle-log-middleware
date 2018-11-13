<?php

namespace Gmponos\GuzzleLogger\Handler\LogLevel;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

/**
 * This class is the default one that will be used in Handlers to determine the log level.
 *
 * @author George Mponos <gmponos@gmail.com>
 */
final class StatusCodeStrategy implements LogLevelStrategyInterface
{
    /**
     * @var array
     */
    private $logCodeLevel = [];

    public function __construct($min)
    {
    }

    /**
     * Returns the log level for a response.
     *
     * @param RequestInterface|ResponseInterface|TransferStats|\Exception $value
     * @param array $options
     * @return string LogLevel
     */
    public function getLevel($value, array $options = []): string
    {
        $this->setOptions($options);
        if ($value instanceof \Exception) {
            return LogLevel::ERROR;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        return LogLevel::DEBUG;
    }

    /**
     * @param array $options
     * @return void
     */
    private function setOptions(array $options): void
    {
        if (!isset($options['log'])) {
            return;
        }

        $options = $options['log'];

        $options = array_merge([
            'levels' => [],
        ], $options);

        $this->logCodeLevel = $options['levels'];
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseLevel(ResponseInterface $response): string
    {
        $code = $response->getStatusCode();
        if ($code === 0) {
            return LogLevel::ERROR;
        }

        if (isset($this->logCodeLevel[$code])) {
            return $this->logCodeLevel[$code];
        }

        return LogLevel::DEBUG;
    }
}
