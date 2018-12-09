<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use Exception;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Classes that will implement this interface are responsible
 * to log the MessageInterface|\Exception|TransferStats that are
 * passed as values.
 *
 * @author George Mponos <gmponos@gmail.com>
 */
interface HandlerInterface
{
    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $exception
     * @param TransferStats $stats
     * @param array $options
     * @return void
     */
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?Exception $exception,
        ?TransferStats $stats,
        array $options
    ): void;
}
