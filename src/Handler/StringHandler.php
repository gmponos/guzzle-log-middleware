<?php

namespace Gmponos\GuzzleLogger\Handler;

use Gmponos\GuzzleLogger\Handler\Exception\UnsupportedException;
use Gmponos\GuzzleLogger\Handler\LogLevel\LogLevelStrategy;
use Gmponos\GuzzleLogger\Handler\LogLevel\LogLevelStrategyInterface;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;

final class StringHandler implements HandlerInterface
{
    /**
     * @var LogLevelStrategyInterface
     */
    private $logLevelStrategy;

    public function __construct(LogLevelStrategyInterface $logLevelStrategy = null)
    {
        $this->logLevelStrategy = $logLevelStrategy = null ? new LogLevelStrategy() : $logLevelStrategy;
    }

    public function log(LoggerInterface $logger, $value, array $options = [])
    {
        $level = $this->logLevelStrategy->getLevel($value, $options);
        if ($value instanceof MessageInterface) {
            $logger->log($level, 'Guzzle HTTP message', ['message' => \GuzzleHttp\Psr7\str($value)]);
            return;
        }

        if ($value instanceof \Exception) {
            $logger->log($level, 'Guzzle HTTP exception', ['exception' => $value]);
            return;
        }

        if ($value instanceof TransferStats) {
            $logger->log($level, sprintf(
                'Guzzle HTTP transfer time: %s for uri: %s',
                $value->getTransferTime(),
                $value->getEffectiveUri()
            ));
            return;
        }

        throw new UnsupportedException();
    }
}