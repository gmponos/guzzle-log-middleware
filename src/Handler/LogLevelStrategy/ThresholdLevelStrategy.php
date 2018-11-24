<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Handler\LogLevelStrategy;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class ThresholdLevelStrategy implements LogLevelStrategyInterface
{
    public const CLIENT_ERRORS = '4xx';
    public const SERVER_ERRORS = '5xx';

    private $matchingStatusCodes = [
        self::CLIENT_ERRORS => 4,
        self::SERVER_ERRORS => 5,
    ];

    /**
     * @var array
     */
    private $thresholds = [
        self::CLIENT_ERRORS => LogLevel::ERROR,
        self::SERVER_ERRORS => LogLevel::CRITICAL,
    ];

    /**
     * @var string
     */
    private $exceptionLevel;

    /**
     * @var string
     */
    private $defaultLevel;

    /**
     * @param array $thresholds
     * @param string $defaultLevel
     * @param string $exceptionLevel
     */
    public function __construct(
        array $thresholds = [],
        string $defaultLevel = LogLevel::DEBUG,
        string $exceptionLevel = LogLevel::CRITICAL
    ) {
        $this->exceptionLevel = $exceptionLevel;
        $this->defaultLevel = $defaultLevel;
        $this->thresholds = array_merge([
            '4xx' => LogLevel::ERROR,
            '5xx' => LogLevel::CRITICAL,
        ], $thresholds);
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
            return $this->exceptionLevel;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        return $this->defaultLevel;
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
            return $this->exceptionLevel;
        }

        $codeLevel = (int)($code / 100);
        $key = array_search($codeLevel, $this->matchingStatusCodes, true);
        if ($key === false) {
            return $this->defaultLevel;
        }

        return $this->thresholds[$key];
    }
}
