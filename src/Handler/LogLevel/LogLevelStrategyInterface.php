<?php

namespace Gmponos\GuzzleLogger\Handler\LogLevel;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;

/**
 * Classes that will implement this interface will be able to determine the log level.
 */
interface LogLevelStrategyInterface
{
    /**
     * Returns the log level.
     *
     * @param MessageInterface|\Exception|TransferStats $value
     * @param array $options
     * @return string
     */
    public function getLevel($value, array $options = []);
}
