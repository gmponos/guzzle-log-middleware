<?php

namespace Gmponos\GuzzleLogger\Handler\LogLevel;

interface LogLevelStrategyInterface
{
    public function getLevel($value, array $options = []);
}