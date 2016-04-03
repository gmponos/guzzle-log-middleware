<?php

namespace Gmponos\GuzzleLogger\Test\TestApp;

use Psr\Log\LoggerInterface;

class HistoryLogger implements LoggerInterface
{

    /**
     * @var array
     */
    public $history = [];

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function emergency($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function alert($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function critical($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function error($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function warning($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function notice($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function info($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function debug($message, array $context = [])
    {
        $this->history[] = [
            'level' => __FUNCTION__,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $this->history[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
