<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler\LogLevelStrategy;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LogLevel;

/**
 * Classes that will implement this interface will be able to determine the log level.
 */
interface LogLevelStrategyInterface
{
    /**
     * A mapping of LogLevels that exist. This mapping can be used for validation.
     */
    public const LEVELS = [
        LogLevel::EMERGENCY => LogLevel::EMERGENCY,
        LogLevel::ALERT => LogLevel::ALERT,
        LogLevel::CRITICAL => LogLevel::CRITICAL,
        LogLevel::ERROR => LogLevel::ERROR,
        LogLevel::WARNING => LogLevel::WARNING,
        LogLevel::NOTICE => LogLevel::NOTICE,
        LogLevel::INFO => LogLevel::INFO,
        LogLevel::DEBUG => LogLevel::DEBUG,
    ];

    /**
     * Returns the log level.
     *
     * @param MessageInterface|\Throwable|TransferStats $value
     */
    public function getLevel($value, array $options): string;
}
