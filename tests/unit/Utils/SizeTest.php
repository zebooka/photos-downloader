<?php

namespace Zebooka\Utils;

use PHPUnit\Framework\TestCase;

class SizeTest extends TestCase
{
    public function test_decimal()
    {
        $this->assertEquals('0 B', Size::humanReadableSize(0, false));
        $this->assertEquals('1 GB', Size::humanReadableSize(1234567890, false, 0));
        $this->assertEquals('2 GB', Size::humanReadableSize(1500000000, false, 0));
        $this->assertEquals('1.235 GB', Size::humanReadableSize(1234567890, false, 3));
        $this->assertEquals('-124 kB', Size::humanReadableSize(-123500, false, 0));
        $this->assertEquals('1 YB', Size::humanReadableSize('1000000000000000000000000', false));

        $this->assertEquals('1 B', Size::humanReadableSize(1, false));
        $this->assertEquals('1 kB', Size::humanReadableSize(1000, false));
        $this->assertEquals('1 MB', Size::humanReadableSize(1000000, false));
        $this->assertEquals('1 GB', Size::humanReadableSize(1000000000, false));
        $this->assertEquals('1 TB', Size::humanReadableSize(1000000000000, false));
        $this->assertEquals('1 PB', Size::humanReadableSize(1000000000000000, false));
        $this->assertEquals('1 EB', Size::humanReadableSize(1000000000000000000, false));
        $this->assertEquals('1 ZB', Size::humanReadableSize(1000000000000000000000, false));
        $this->assertEquals('1 YB', Size::humanReadableSize(1000000000000000000000000, false));
    }

    public function test_binary()
    {
        $this->assertEquals('0 B', Size::humanReadableSize(0, true));
        $this->assertEquals('1 KiB', Size::humanReadableSize(1024, true));
        $this->assertEquals('1.5 MiB', Size::humanReadableSize(1572864, true));
        $this->assertEquals('-42 KiB', Size::humanReadableSize(-43008, true));
        $this->assertEquals('1 YiB', Size::humanReadableSize('1208925819614629174706176', true));

        $this->assertEquals('1 B', Size::humanReadableSize(1, true));
        $this->assertEquals('1 KiB', Size::humanReadableSize(1024, true));
        $this->assertEquals('1 MiB', Size::humanReadableSize(1048576, true));
        $this->assertEquals('1 GiB', Size::humanReadableSize(1073741824, true));
        $this->assertEquals('1 TiB', Size::humanReadableSize(1099511627776, true));
        $this->assertEquals('1 PiB', Size::humanReadableSize(1125899906842624, true));
        $this->assertEquals('1 EiB', Size::humanReadableSize(1152921504606846976, true));
        $this->assertEquals('1 ZiB', Size::humanReadableSize(1180591620717411303424, true));
        $this->assertEquals('1 YiB', Size::humanReadableSize(1208925819614629174706176, true));
    }
}
