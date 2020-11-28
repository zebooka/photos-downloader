<?php

namespace Zebooka\PD;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function test_factory()
    {
        $configure = \Mockery::mock(Configure::class);
        $logger = LoggerFactory::logger($configure);
        $this->assertInstanceOf(Logger::class, $logger);
    }
}
