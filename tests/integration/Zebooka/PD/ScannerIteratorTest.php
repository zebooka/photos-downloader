<?php

namespace Zebooka\PD;

class ScannerIteratorTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/scanner';
    }

    public function test_iteration()
    {
        $scannerIterator = new ScannerIterator(array($this->resourceDirectory()));
        $this->assertInstanceOf('\\Traversable', $scannerIterator);
        $i = 0;
        foreach ($scannerIterator as $photoBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\PhotoBunch', $photoBunch);
        }
        $this->assertEquals(4, $i);
        // we can restart iteration
        $i = 0;
        foreach ($scannerIterator as $photoBunch) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\PhotoBunch', $photoBunch);
        }
        $this->assertEquals(4, $i);
    }
}
