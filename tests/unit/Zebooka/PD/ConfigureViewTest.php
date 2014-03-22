<?php

namespace Zebooka\PD;

class ConfigureViewTest extends \PHPUnit_Framework_TestCase
{
    public function test_rendering()
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure')
            ->shouldReceive('argv')
            ->once()
            ->andReturn(array('unique-usage-example', '123'))
            ->getMock();
        $translator = \Mockery::mock('\\Zebooka\\Translator\\Translator')
            ->shouldReceive('translate')
            ->atLeast()
            ->once()
            ->andReturn('unique-string')
            ->getMock();
        $view = new ConfigureView($configure, $translator);
        $text = $view->render();
        $this->assertInternalType('string', $text);
        $this->assertContains('unique-string', $text);
        $this->assertContains('unique-usage-example', $text);
        $this->assertContains('123', $text);
    }
}
