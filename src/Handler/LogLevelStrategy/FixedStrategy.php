<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler\LogLevelStrategy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LogLevel;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class FixedStrategy implements LogLevelStrategyInterface
{
    /**
     * The default LogLevel that it will used for the non-exceptions.
     *
     * @var string
     */
    private $defaultLevel;

    /**
     * The log Level in cases of exceptions.
     *
     * @var string
     */
    private $exceptionLevel;

    /**
     * The log level of the statistics
     *
     * @var string
     */
    private $statsLevel;

    public function __construct(
        string $defaultLevel = LogLevel::DEBUG,
        string $exceptionLevel = null,
        string $statsLevel = null
    ) {
        $this->defaultLevel = $defaultLevel;
        $this->exceptionLevel = $exceptionLevel ?? $defaultLevel;
        $this->statsLevel = $statsLevel ?? $defaultLevel;
    }

    /**
     * Returns the log level.
     *
     * @param MessageInterface|\Exception|TransferStats $value
     * @param array $options
     * @return string
     */
    public function getLevel($value, array $options): string
    {
        if ($value instanceof RequestException) {
            return $this->defaultLevel;
        }

        if ($value instanceof \Exception) {
            return $this->exceptionLevel;
        }

        if ($value instanceof TransferStats) {
            return $this->statsLevel;
        }

        return $this->defaultLevel;
    }
}
