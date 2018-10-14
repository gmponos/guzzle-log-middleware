<?php

namespace Gmponos\GuzzleLogger\Handler;

use Psr\Log\LoggerInterface;

interface HandlerInterface
{
    public function log(LoggerInterface $logger, $value, array $options = []);
}