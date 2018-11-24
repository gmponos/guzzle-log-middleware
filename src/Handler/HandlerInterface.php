<?php

declare(strict_types=1);

namespace Gmponos\GuzzleLogger\Handler;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Classes that will implement this interface are responsible
 * to log the MessageInterface|\Exception|TransferStats that are
 * passed as value.
 */
interface HandlerInterface
{
    /**
     * @param LoggerInterface $logger
     * @param MessageInterface|\Exception|TransferStats $value
     * @param array $options
     * @return void
     */
    public function log(LoggerInterface $logger, $value, array $options = []): void;
}
