<?php

namespace Zebooka\PD;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function test_process()
    {
        $photoBunch = \Mockery::mock('\\Zebooka\\PD\\PhotoBunch');

        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $logger = \Mockery::mock('\\Monolog\\Logger')->shouldIgnoreMissing();
        $translator = \Mockery::mock('\\Zebooka\\PD\\Translator');
        $processor = new Processor($configure, $logger, $translator);

        $processor->process($photoBunch);
    }
}
