<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class FileBunchTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/exif';
    }

    public function test_creation()
    {
        foreach (Scanner::supportedExtensions() as $photoExtension) {
            // test with not-photo-extension
            $extensions = array($photoExtension, 'not-photo-extension');
            $fileBunch = new FileBunch('unique-bunchId', $extensions);
            $this->assertEquals('unique-bunchId.{' . implode(',', $extensions) . '}', strval($fileBunch));

            // test with single extension
            $fileBunch = new FileBunch('unique-bunchId', array($photoExtension));
            $this->assertEquals('unique-bunchId.' . $photoExtension, strval($fileBunch));
        }
    }

    public function test_methods()
    {
        $extensions = Scanner::supportedExtensions();
        $fileBunch = new FileBunch('unique-bunchId', $extensions);
        $this->assertEquals('.', $fileBunch->directory());
        $this->assertEquals('unique-bunchId', $fileBunch->basename());
        $this->assertEquals($extensions, $fileBunch->extensions());

        $fileBunch = new FileBunch('/unique-bunchId', $extensions);
        $this->assertEquals('/', $fileBunch->directory());
        $this->assertEquals('unique-bunchId', $fileBunch->basename());
        $this->assertEquals($extensions, $fileBunch->extensions());

        $fileBunch = new FileBunch('/directory/unique-bunchId', $extensions);
        $this->assertEquals('/directory', $fileBunch->directory());
        $this->assertEquals('unique-bunchId', $fileBunch->basename());
        $this->assertEquals($extensions, $fileBunch->extensions());
    }

    public function test_allExtensions()
    {
        $primaryExtensions = Scanner::supportedExtensions();
        shuffle($primaryExtensions);
        $secondaryExtensions = array('secondary-extension-1', 'secondary-extension-2', 'secondary-extension-3');
        shuffle($secondaryExtensions);

        $fileBunch = new FileBunch('unique-bunchId', $primaryExtensions, $secondaryExtensions);
        $this->assertEquals(Scanner::supportedExtensions(), $fileBunch->primaryExtensions());
        $this->assertEquals($secondaryExtensions, $fileBunch->secondaryExtensions());
    }

    public function test_photoExtensions()
    {
        $primaryExtensions = Scanner::supportedPhotoExtensions();
        shuffle($primaryExtensions);
        $secondaryExtensions = array('secondary-extension-1', 'secondary-extension-2', 'secondary-extension-3');
        shuffle($secondaryExtensions);

        $fileBunch = new FileBunch('unique-bunchId', $primaryExtensions, $secondaryExtensions);
        $this->assertEquals(Scanner::supportedPhotoExtensions(), $fileBunch->primaryExtensions());
        $this->assertEquals($secondaryExtensions, $fileBunch->secondaryExtensions());
    }

    public function test_videoExtensions()
    {
        $primaryExtensions = Scanner::supportedVideoExtensions();
        shuffle($primaryExtensions);
        $secondaryExtensions = array('secondary-extension-1', 'secondary-extension-2', 'secondary-extension-3');
        shuffle($secondaryExtensions);

        $fileBunch = new FileBunch('unique-bunchId', $primaryExtensions, $secondaryExtensions);
        $this->assertEquals(Scanner::supportedVideoExtensions(), $fileBunch->primaryExtensions());
        $this->assertEquals($secondaryExtensions, $fileBunch->secondaryExtensions());
    }

    public function test_failure_on_empty_extensions()
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                'Empty primaryExtensions list passed.',
                FileBunch::ERROR_EMPTY_EXTENSIONS
            )
        );
        new FileBunch('unique-bunchId', array());
    }

    public function test_exifs()
    {
        $fileBunch = new FileBunch($this->resourceDirectory() . '/cubie', array('jpg'));
        $exifs = $fileBunch->exifs();
        $this->assertInternalType('array', $exifs);
        $this->assertCount(1, $exifs);
        $this->assertArrayHasKey('jpg', $exifs);
        $this->assertInstanceOf('\\Zebooka\\PD\\Exif', $exifs['jpg']);

        $exifs2 = $fileBunch->exifs();
        $this->assertSame($exifs, $exifs2);
    }
}
