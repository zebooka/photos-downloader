<?php

namespace Zebooka\PD;

class ScannerTest extends \PHPUnit_Framework_TestCase
{
    private $oldcwd;

    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/scanner';
    }

    public function test_searchForNextFile_recursive()
    {
        $scanner = new Scanner(array($this->resourceDirectory() . '/0.jpg', $this->resourceDirectory()), true);
        $i = 0;
        while ($fileBunch = $scanner->searchForNextFile()) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
            if (array_intersect($fileBunch->primaryExtensions(), Scanner::supportedPhotoExtensions())) {
                $this->assertCount(0, array_intersect($fileBunch->primaryExtensions(), Scanner::supportedVideoExtensions()));
            }
            if (array_intersect($fileBunch->primaryExtensions(), Scanner::supportedVideoExtensions())) {
                $this->assertCount(0, array_intersect($fileBunch->primaryExtensions(), Scanner::supportedPhotoExtensions()));
            }
        }
        $this->assertEquals(6, $i);
    }

    public function test_searchForNextFile_not_recursive()
    {
        $scanner = new Scanner(array($this->resourceDirectory() . '/0.jpg', $this->resourceDirectory()), false);
        $i = 0;
        while ($fileBunch = $scanner->searchForNextFile()) {
            $i++;
            $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
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

    public function test_files_without_directory_are_correctly_processed()
    {
        $scanner = new Scanner(array('-'), false, $this->resourceDirectory() . '/list.txt');

        $fileBunch = $scanner->searchForNextFile();
        $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        $this->assertEquals(realpath($this->resourceDirectory()), $fileBunch->directory());

        $fileBunch = $scanner->searchForNextFile();
        $this->assertFalse($fileBunch);
    }

    protected function setUp()
    {
        $this->oldcwd = getcwd();
        chdir($this->resourceDirectory());
    }

    protected function tearDown()
    {
        chdir($this->oldcwd);
    }
}
