<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;
use Zebooka\Translator\Translator;

class ConfigureViewTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function test_rendering()
    {
        $configure = \Mockery::mock(Configure::class)
            ->shouldReceive('argv')
            ->once()
            ->andReturn(array('unique-usage-example', '123'))
            ->getMock();
        $translator = \Mockery::mock(Translator::class)
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
