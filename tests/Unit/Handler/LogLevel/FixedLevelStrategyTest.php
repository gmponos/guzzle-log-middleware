<?php

namespace Gmponos\GuzzleLogger\Test\Unit\Handler\LogLevel;

use Gmponos\GuzzleLogger\Handler\LogLevel\FixedLevelStrategy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class FixedLevelStrategyTest extends TestCase
{
    /**
     * @test
     * @dataProvider valueProvider
     * @param mixed $value
     */
    public function fixedDebugLevelForAllValues($value)
    {
        $strategy = new FixedLevelStrategy(LogLevel::DEBUG);
        $this->assertSame('debug', $strategy->getLevel($value, []));
    }

    public function valueProvider(): array
    {
        return [
            [new Request('get', 'www.test.com')],
            [new Response()],
            [new \Exception()],
            [new RequestException('Not Found', new Request('get', 'www.test.com'))],
        ];
    }
}
