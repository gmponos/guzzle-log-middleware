<?php

namespace Gmponos\GuzzleLogger\Handler\LogLevel;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LogLevel;

final class FixedLevelStrategy implements LogLevelStrategyInterface
{
    private $defaultLevel;

    private $exceptionLevel;

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
        if ($value instanceof \Exception) {
            return $this->exceptionLevel;
        }

        if ($value instanceof TransferStats) {
            return $this->statsLevel;
        }

        return $this->defaultLevel;
    }
}
