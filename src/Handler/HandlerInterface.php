<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Classes that will implement this interface are responsible
 * to log the MessageInterface|\Throwable|TransferStats that are
 * passed as values.
 *
 * @author George Mponos <gmponos@gmail.com>
 */
interface HandlerInterface
{
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $exception = null,
        ?TransferStats $stats = null,
        array $options = []
    ): void;
}
