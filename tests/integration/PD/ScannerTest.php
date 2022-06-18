<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ScannerTest extends TestCase
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
        $this->assertEquals(8, $i);
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
        $this->assertIsArray($extensions);
        foreach ($extensions as $extension) {
            $this->assertIsString($extension);
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

    public function test_files_without_extensions_are_correctly_processed_in_bunches()
    {
        $scanner = new Scanner(['d'], false);
        $fileBunch = $scanner->searchForNextFile();
        $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        $this->assertEquals(['jpg'], $fileBunch->primaryExtensions());
        $this->assertEquals([''], $fileBunch->secondaryExtensions());
    }

    public function test_dirs_without_extensions_are_not_processed_in_bunches()
    {
        $scanner = new Scanner(['e'], false);
        $fileBunch = $scanner->searchForNextFile();
        $this->assertInstanceOf('\\Zebooka\\PD\\FileBunch', $fileBunch);
        $this->assertEquals(['jpg'], $fileBunch->primaryExtensions());
        $this->assertEquals([], $fileBunch->secondaryExtensions());
    }

    protected function setUp(): void
    {
        $this->oldcwd = getcwd();
        chdir($this->resourceDirectory());
    }

    protected function tearDown(): void
    {
        chdir($this->oldcwd);
    }
}
