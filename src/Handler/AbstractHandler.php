<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Handler;

use Gmponos\GuzzleLogger\Handler\LogLevelStrategy\FixedStrategy;
use Gmponos\GuzzleLogger\Handler\LogLevelStrategy\LogLevelStrategyInterface;

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
        return new FixedStrategy();
    }
}
