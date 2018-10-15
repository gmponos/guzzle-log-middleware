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
class LogLevelStrategy implements LogLevelStrategyInterface
{
    /**
     * @var array
     */
    private $thresholds = [
        LogLevel::WARNING => 399,
        LogLevel::ERROR => 499,
    ];

    /**
     * @var array
     */
    private $logCodeLevel = [];

    /**
     * Returns the log level for a response.
     *
     * @param RequestInterface|ResponseInterface|TransferStats|\Exception $value
     * @param array $options
     * @return string LogLevel
     */
    public function getLevel($value, array $options = [])
    {
        $this->setOptions($options);
        if ($value instanceof \Exception) {
            return LogLevel::CRITICAL;
        }

        if ($value instanceof RequestInterface) {
            return LogLevel::DEBUG;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        if ($value instanceof TransferStats) {
            return LogLevel::DEBUG;
        }

        return LogLevel::DEBUG;
    }

    /**
     * @param array $options
     * @return void
     */
    private function setOptions(array $options)
    {
        if (!isset($options['log'])) {
            return;
        }
        $options = $options['log'];

        $options = array_merge([
            'warning_threshold' => 399,
            'error_threshold' => 499,
            'levels' => [],
        ], $options);

        $this->logCodeLevel = $options['levels'];
        $this->thresholds['warning'] = $options['warning_threshold'];
        $this->thresholds['error'] = $options['error_threshold'];
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseLevel(ResponseInterface $response)
    {
        $code = $response->getStatusCode();
        if ($code === 0) {
            return LogLevel::CRITICAL;
        }

        if (isset($this->logCodeLevel[$code])) {
            return $this->logCodeLevel[$code];
        }

        if ($this->thresholds['error'] !== null && $code > $this->thresholds['error']) {
            return LogLevel::CRITICAL;
        }

        if ($this->thresholds['warning'] !== null && $code > $this->thresholds['warning']) {
            return LogLevel::ERROR;
        }

        return LogLevel::DEBUG;
    }
}
