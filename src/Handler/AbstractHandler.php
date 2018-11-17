<?php

namespace Gmponos\GuzzleLogger\Handler;

use Gmponos\GuzzleLogger\Handler\LogLevel\FixedLevelStrategy;
use Gmponos\GuzzleLogger\Handler\LogLevel\LogLevelStrategyInterface;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var LogLevelStrategyInterface
     */
    protected $logLevelStrategy;

    protected function getDefaultStrategy(): LogLevelStrategyInterface
    {
        return new FixedLevelStrategy();
    }
}
