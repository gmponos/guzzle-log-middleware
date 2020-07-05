<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler\LogLevelStrategy;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class ThresholdStrategy implements LogLevelStrategyInterface
{
    public const INFORMATIONAL = '1xx';
    public const SUCCESS = '2xx';
    public const REDIRECTION = '3xx';
    public const CLIENT_ERRORS = '4xx';
    public const SERVER_ERRORS = '5xx';

    /**
     * @var array<string, int>
     */
    private $matchingStatusCodes = [
        self::INFORMATIONAL => 1,
        self::SUCCESS => 2,
        self::REDIRECTION => 3,
        self::CLIENT_ERRORS => 4,
        self::SERVER_ERRORS => 5,
    ];

    /**
     * @var array
     */
    private $thresholds = [
        self::INFORMATIONAL => LogLevel::DEBUG,
        self::SUCCESS => LogLevel::INFO,
        self::REDIRECTION => LogLevel::NOTICE,
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
     * Developer can initialize this strategy passing an array of thresholds
     *
     * @param array $thresholds An array of thresholds.
     * @param string $defaultLevel The that will be used for the requests and as a default one.
     */
    public function __construct(
        array $thresholds = [],
        string $defaultLevel = LogLevel::DEBUG,
        string $exceptionLevel = LogLevel::CRITICAL
    ) {
        $this->exceptionLevel = $exceptionLevel;
        $this->defaultLevel = $defaultLevel;
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    public function getLevel($value, array $options): string
    {
        if ($value instanceof \Throwable) {
            return $this->exceptionLevel;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        return $this->defaultLevel;
    }

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
