<?php

namespace Zebooka\PD;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

class LoggerFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function test_factory()
    {
        $input = \Mockery::mock(InputInterface::class);
        $logger = LoggerFactory::logger($input);
        $this->assertInstanceOf(Logger::class, $logger);
    }
}
