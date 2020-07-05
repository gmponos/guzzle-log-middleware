<?php

declare(strict_types=1);

namespace GuzzleLogMiddleware\Handler\LogLevelStrategy;

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
     * @phpstan-param LogLevel::* $defaultLevel
     * @phpstan-param LogLevel::* $exceptionLevel
     */
    public function __construct(string $defaultLevel = LogLevel::DEBUG, string $exceptionLevel = LogLevel::CRITICAL)
    {
        $this->exceptionLevel = $exceptionLevel;
        $this->defaultLevel = $defaultLevel;
    }

    /**
     * Sets a logging level per status code.
     */
    public function setLevel(int $statusCode, string $level): void
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new \InvalidArgumentException('Invalid status code passed');
        }
        $this->statusCodeLevels[$statusCode] = $level;
    }

    public function getLevel($value, array $options): string
    {
        $this->setOptions($options);
        if ($value instanceof \Throwable) {
            return $this->exceptionLevel;
        }

        if ($value instanceof ResponseInterface) {
            return $this->getResponseLevel($value);
        }

        return $this->defaultLevel;
    }

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
