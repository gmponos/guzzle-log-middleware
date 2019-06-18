<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler;

use Exception;
use GuzzleHttp\TransferStats;
use GuzzleLogMiddleware\Handler\LogLevelStrategy\LogLevelStrategyInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class MultiRecordArrayHandler extends AbstractHandler
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
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param Exception|null $exception
     * @param TransferStats|null $stats
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
    ): void {
        $this->logRequest($logger, $request, $options);

        if ($stats !== null) {
            $this->logStats($logger, $stats, $options);
        }

        if ($response !== null) {
            $this->logResponse($logger, $response, $options);
        } else {
            $this->logReason($logger, $exception, $options);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param array $options
     * @return void
     */
    private function logRequest(LoggerInterface $logger, RequestInterface $request, array $options): void
    {
        $context['request']['method'] = $request->getMethod();
        $context['request']['headers'] = $request->getHeaders();
        $context['request']['uri'] = $request->getRequestTarget();
        $context['request']['version'] = 'HTTP/' . $request->getProtocolVersion();

        if ($request->getBody()->getSize() > 0) {
            $context['request']['body'] = $this->formatBody($request, $options);
        }

        $level = $this->logLevelStrategy->getLevel($request, $options);
        $logger->log($level, 'Guzzle HTTP request', $context);
    }

    /**
     * @param LoggerInterface $logger
     * @param ResponseInterface|null $response
     * @param array $options
     * @return void
     */
    private function logResponse(LoggerInterface $logger, ?ResponseInterface $response, array $options): void
    {
        $context['response']['headers'] = $response->getHeaders();
        $context['response']['status_code'] = $response->getStatusCode();
        $context['response']['version'] = 'HTTP/' . $response->getProtocolVersion();
        $context['response']['message'] = $response->getReasonPhrase();

        if ($response->getBody()->getSize() > 0) {
            $context['response']['body'] = $this->formatBody($response, $options);
        }

        $level = $this->logLevelStrategy->getLevel($response, $options);
        $logger->log($level, 'Guzzle HTTP response', $context);
    }

    /**
     * @param LoggerInterface $logger
     * @param Exception|null $exception
     * @param array $options
     * @return void
     */
    private function logReason(LoggerInterface $logger, ?Exception $exception, array $options): void
    {
        if ($exception === null) {
            return;
        }

        $context['reason']['code'] = $exception->getCode();
        $context['reason']['message'] = $exception->getMessage();
        $context['reason']['line'] = $exception->getLine();
        $context['reason']['file'] = $exception->getFile();

        $level = $this->logLevelStrategy->getLevel($exception, $options);
        $logger->log($level, 'Guzzle HTTP exception', $context);
    }

    /**
     * @param LoggerInterface $logger
     * @param TransferStats|null $stats
     * @param array $options
     * @return void
     */
    private function logStats(LoggerInterface $logger, ?TransferStats $stats, array $options): void
    {
        $this->logLevelStrategy->getLevel($stats, $options);
        $logger->debug('Guzzle HTTP statistics', [
            'time' => $stats->getTransferTime(),
            'uri' => $stats->getEffectiveUri(),
        ]);
    }

    /**
     * @param MessageInterface $message
     * @param array $options
     * @return string|array
     */
    private function formatBody(MessageInterface $message, array $options)
    {
        $stream = $message->getBody();
        if ($stream->isSeekable() === false || $stream->isReadable() === false) {
            return 'Body stream is not seekable/readable.';
        }

        if (isset($options['log']['sensitive']) && $options['log']['sensitive'] === true) {
            return 'Body contains sensitive information therefore it is not included.';
        }

        if ($stream->getSize() >= 3500) {
            $summary = $stream->read(200) . ' (truncated...)';
            $stream->rewind();
            return $summary;
        }

        $body = $stream->getContents();
        $isJson = preg_grep('/application\/[\w\.\+]*(json)/', $message->getHeader('Content-Type'));
        if (!empty($isJson)) {
            $body = json_decode($body, true);
        }

        $stream->rewind();
        return $body;
    }
}
