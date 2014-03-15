<?php

namespace Zebooka\PD;

class ConfigureViewTest extends \PHPUnit_Framework_TestCase
{
    public function test_rendering()
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $translator = \Mockery::mock('\\Zebooka\\PD\\Translator')
            ->shouldReceive('translate')
            ->atLeast()
            ->once()
            ->andReturn('unique-string')
            ->getMock();
        $view = new ConfigureView($configure, $translator);
        $text = $view->render();
        $this->assertInternalType('string', $text);
        $this->assertContains('unique-string', $text);
    }
}
