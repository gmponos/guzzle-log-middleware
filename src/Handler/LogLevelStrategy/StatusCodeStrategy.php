<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler\LogLevelStrategy;

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
    private $statusCodeLevels;

    /**
     * @var string
     */
    private $exceptionLevel;

    /**
     * @var string
     */
    private $defaultLevel;

    /**
     * @param string $defaultLevel
     * @param string $exceptionLevel
     */
    public function __construct($defaultLevel = LogLevel::DEBUG, $exceptionLevel = LogLevel::CRITICAL)
    {
        $this->exceptionLevel = $exceptionLevel;
        $this->defaultLevel = $defaultLevel;
    }

    /**
     * Sets a logging level per status code.
     *
     * @param int $statusCode
     * @param string $level
     * @return void
     */
    public function setLevel(int $statusCode, string $level): void
    {
        // todo validate the level and status code.
        $this->statusCodeLevels[$statusCode] = $level;
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

        if (!isset($options['levels'])) {
            return;
        }

        $this->statusCodeLevels = $this->statusCodeLevels + $options['levels'];
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

        if (isset($this->statusCodeLevels[$code])) {
            return $this->statusCodeLevels[$code];
        }

        return $this->defaultLevel;
    }
}
