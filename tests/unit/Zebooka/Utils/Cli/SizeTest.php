<?php

namespace Zebooka\Utils\Cli;

use PHPUnit\Framework\TestCase;

class SizeTest extends TestCase
{
    public function test_getTerminalWidth()
    {
        $size = Size::getTerminalWidth();
        $this->assertNotEquals(false, $size);
        $this->assertNotEquals(null, $size);
        $this->assertInternalType('int', $size);
    }
}
