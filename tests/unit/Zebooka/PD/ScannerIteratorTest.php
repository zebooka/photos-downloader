<?php

namespace Zebooka\PD;

class ScannerIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function test_empty_with_empty_iterator()
    {
        $si = new ScannerIterator(new \ArrayIterator(array()));
        $this->assertCount(0, $si->getIterator());
    }

    public function test_empty_with_not_supported_files()
    {
        $si = new ScannerIterator(new \ArrayIterator(array(
            '/a/b/c.txt',
            '/a/d.xmp',
            '/e.png',
            'f.gif',
            'g.jp2',
        )));
        $this->assertCount(0, $si->getIterator());
    }

    public function test_with_supported_files()
    {
        $si = new ScannerIterator(new \ArrayIterator(array(
            '/a/b/c.txt',
            '/a/d.jpg',
            '/e.dng',
            'f.tiff',
            'g.jp2',
        )));
        $this->assertCount(3, $si->getIterator());
    }
}
