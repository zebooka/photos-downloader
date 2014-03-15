<?php

namespace Zebooka\PD;

class LoggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function test_factory()
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $logger = LoggerFactory::logger($configure);
        $this->assertInstanceOf('\\Monolog\\Logger', $logger);
    }
}
