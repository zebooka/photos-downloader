<?php

namespace Zebooka\PD;

class ScannerTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/scanner';
    }

    public function test_searchForNextFile_recursive()
    {
        $scanner = new Scanner(array($this->resourceDirectory().'/0.jpg', $this->resourceDirectory()), true);
        $i = 0;
        while ($photoBunch = $scanner->searchForNextFile()) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\PhotoBunch', $photoBunch);
        }
        $this->assertEquals(5, $i);
    }

    public function test_searchForNextFile_not_recursive()
    {
        $scanner = new Scanner(array($this->resourceDirectory().'/0.jpg', $this->resourceDirectory()), false);
        $i = 0;
        while ($photoBunch = $scanner->searchForNextFile()) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\PhotoBunch', $photoBunch);
        }
        $this->assertEquals(2, $i);
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
