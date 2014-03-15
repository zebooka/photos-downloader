<?php

namespace Zebooka\PD;

class ConfigureViewTest extends \PHPUnit_Framework_TestCase
{
    public function test_rendering()
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure')
            ->shouldReceive('executableName')
            ->withNoArgs()
            ->once()
            ->andReturn('unique-executable-name')
            ->getMock();
        $translator = \Mockery::mock('\\Zebooka\\PD\\Translator')
            ->shouldReceive('translate')
            ->atLeast()
            ->once()
            ->andReturn('unique-string')
            ->getMock();
        $view = new ConfigureView($configure, $translator);
        $text = $view->render();
        $this->assertInternalType('string', $text);
        $this->assertContains('unique-executable-name', $text);
        $this->assertContains('unique-string', $text);
    }
}
