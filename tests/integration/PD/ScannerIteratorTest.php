<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ScannerIteratorTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../res/scanner';
    }

    public function test_recursive_iteration()
    {
        $expected = 7;
        $scannerIterator = new ScannerIterator(array($this->resourceDirectory()), true);
        $this->assertInstanceOf('\\Traversable', $scannerIterator);
        $i = 0;
        foreach ($scannerIterator as $fileBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        }
        $this->assertEquals($expected, $i);
        // we can restart iteration
        $i = 0;
        foreach ($scannerIterator as $fileBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        }
        $this->assertEquals($expected, $i);
    }

    public function test_not_recursive_iteration()
    {
        $scannerIterator = new ScannerIterator(array($this->resourceDirectory()), false);
        $this->assertInstanceOf('\\Traversable', $scannerIterator);
        $i = 0;
        foreach ($scannerIterator as $fileBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        }
        $this->assertEquals(1, $i);
        // we can restart iteration
        $i = 0;
        foreach ($scannerIterator as $fileBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        }
        $this->assertEquals(1, $i);
    }
}
