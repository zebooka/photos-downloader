<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;
use Zebooka\Translator\Translator;

class ConfigureViewTest extends TestCase
{
    public function tearDown(): void
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
        $this->assertIsString($text);
        $this->assertStringContainsString('unique-string', $text);
        $this->assertStringContainsString('unique-usage-example', $text);
        $this->assertStringContainsString('123', $text);
    }
}
