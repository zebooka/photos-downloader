<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function test_factory()
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $logger = LoggerFactory::logger($configure);
        $this->assertInstanceOf('\\Monolog\\Logger', $logger);
    }
}
