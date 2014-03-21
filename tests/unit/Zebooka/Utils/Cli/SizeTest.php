<?php

namespace Zebooka\Utils\Cli;

class SizeTest extends \PHPUnit_Framework_TestCase
{
    public function test_getTerminalWidth()
    {
        $size = Size::getTerminalWidth();
        $this->assertNotEquals(false, $size);
        $this->assertNotEquals(null, $size);
        $this->assertInternalType('int', $size);
    }
}
