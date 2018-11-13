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
final class ThresholdLevelStrategy implements LogLevelStrategyInterface
{
    private $matchingStatusCodes = [
        '4xx' => 4,
        '5xx' => 5,
    ];

    /**
     * @var array
     */
    private $thresholds = [
        '4xx' => LogLevel::ERROR,
        '5xx' => LogLevel::CRITICAL,
    ];

    private $exceptionLevel;

    public function __construct(array $thresholds, string $exceptionLevel = null)
    {
        if(!in_array($exceptionLevel, LogLevel::$levels, true)){
            throw new \Exception();
        }
        $this->thresholds = array_merge([
            '4xx' => LogLevel::ERROR,
            '5xx' => LogLevel::CRITICAL,
        ], $thresholds);

        $this->exceptionLevel = $exceptionLevel === null ? LogLevel::CRITICAL : $exceptionLevel;
    }

    /**
     * Returns the log level for a response.
     *
     * @param RequestInterface|ResponseInterface|TransferStats|\Exception $value
     * @param array $options
     * @return string LogLevel
     */
    public function getLevel($value, array $options): string
    {
        $this->setOptions($options);
        if ($value instanceof \Exception) {
            return LogLevel::CRITICAL;
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
            '4xx' => LogLevel::ERROR,
            '5xx' => LogLevel::CRITICAL,
        ], $options);

        $this->thresholds['4xx'] = $options['4xx'];
        $this->thresholds['5xx'] = $options['5xx'];
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseLevel(ResponseInterface $response): string
    {
        $code = $response->getStatusCode();
        if ($code === 0) {
            return LogLevel::CRITICAL;
        }

        if ($this->thresholds['error'] !== null && $code > $this->thresholds['error']) {
            return LogLevel::ERROR;
        }

        if ($this->thresholds['warning'] !== null && $code > $this->thresholds['warning']) {
            return LogLevel::WARNING;
        }

        return LogLevel::DEBUG;
    }
}
