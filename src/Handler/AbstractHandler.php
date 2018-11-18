<?php

namespace Gmponos\GuzzleLogger\Handler;

use Gmponos\GuzzleLogger\Handler\LogLevel\FixedLevelStrategy;
use Gmponos\GuzzleLogger\Handler\LogLevel\LogLevelStrategyInterface;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var LogLevelStrategyInterface
     */
    protected $logLevelStrategy;

    /**
     * @return LogLevelStrategyInterface
     */
    protected function getDefaultStrategy(): LogLevelStrategyInterface
    {
        return new FixedLevelStrategy();
    }
}
