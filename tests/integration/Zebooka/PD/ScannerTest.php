<?php

namespace Zebooka\PD;

class ScannerTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/scanner';
    }

    public function test_searchForNextFile()
    {
        $scanner = new Scanner(array($this->resourceDirectory()));
        $i = 0;
        while ($photoBunch = $scanner->searchForNextFile()) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\PhotoBunch', $photoBunch);
        }
        $this->assertEquals(4, $i);
    }

    public function test_supportedExtensions()
    {
        $extensions = Scanner::supportedExtensions();
        $this->assertInternalType('array', $extensions);
        foreach ($extensions as $extension) {
            $this->assertInternalType('string', $extension);
        }
    }
}
