<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use GuzzleLogMiddleware\Handler\Exception\UnsupportedException;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class StringHandler extends AbstractHandler
{
    /**
     * @param LogLevelStrategyInterface|null $logLevelStrategy
     */
    public function __construct(LogLevelStrategyInterface $logLevelStrategy = null)
    {
        $this->logLevelStrategy = $logLevelStrategy === null ? $this->getDefaultStrategy() : $logLevelStrategy;
    }

    /**
     * @param LoggerInterface $logger
     * @param MessageInterface|\Exception|TransferStats $value
     * @param array $options
     * @return void
     */
    public function log(LoggerInterface $logger, $value, array $options = []): void
    {
        $level = $this->logLevelStrategy->getLevel($value, $options);
        if ($value instanceof MessageInterface) {
            // we do not allow to record the message if the body is not seekable.
            if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
                $logger->warning('StringHandler can not log request/response because the body is not seekable/readable.');
                return;
            }

            $str = \GuzzleHttp\Psr7\str($value);
            if ($value instanceof RequestInterface) {
                $logger->log($level, 'Guzzle HTTP request:' . "\n" . $str);
                return;
            }

            if ($value instanceof ResponseInterface) {
                $logger->log($level, 'Guzzle HTTP response:' . "\n" . $str);
                return;
            }
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
