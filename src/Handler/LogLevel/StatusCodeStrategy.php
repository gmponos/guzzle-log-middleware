<?php

namespace Gmponos\GuzzleLogger\Handler\LogLevel;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

/**
 * @author George Mponos <gmponos@gmail.com>
 */
final class StatusCodeStrategy implements LogLevelStrategyInterface
{
    /**
     * @var array
     */
    private $logCodeLevels;

    /**
     * @var string
     */
    private $exceptionLevel;

    /**
     * @var string
     */
    private $defaultLevel;

    public function __construct(
        array $logCodeLevels,
        $defaultLevel = LogLevel::DEBUG,
        $exceptionLevel = LogLevel::CRITICAL
    ) {
        $this->logCodeLevels = $logCodeLevels;
        $this->exceptionLevel = $exceptionLevel;
        $this->defaultLevel = $defaultLevel;
    }

    /**
     * Returns the log level for a response.
     *
     * @param RequestInterface|ResponseInterface|TransferStats|\Exception $value
     * @param array $options
     * @return string LogLevel
     */
    public function getLevel($value, array $options): string
    {
        $this->setOptions($options);
        if ($value instanceof \Exception) {
            return $this->exceptionLevel;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        return $this->defaultLevel;
    }

    /**
     * @param array $options
     * @return void
     */
    private function setOptions(array $options): void
    {
        if (!isset($options['log'])) {
            return;
        }

        $options = $options['log'];

        $options = array_merge([
            'levels' => [],
        ], $options);

        $this->logCodeLevels = $options['levels'];
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getResponseLevel(ResponseInterface $response): string
    {
        $code = $response->getStatusCode();
        if ($code === 0) {
            return $this->exceptionLevel;
        }

        if (isset($this->logCodeLevel[$code])) {
            return $this->logCodeLevels[$code];
        }

        return $this->defaultLevel;
    }
}
