<?php

namespace Zebooka\PD;

class FileBunchTest extends \PHPUnit_Framework_TestCase
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
            $photoBunch = new FileBunch('unique-bunchId', $extensions);
            $this->assertEquals('unique-bunchId.{' . implode(',', $extensions) . '}', strval($photoBunch));

            // test with single extension
            $photoBunch = new FileBunch('unique-bunchId', array($photoExtension));
            $this->assertEquals('unique-bunchId.' . $photoExtension, strval($photoBunch));
        }
    }

    public function test_methods()
    {
        $extensions = Scanner::supportedExtensions();
        $photoBunch = new FileBunch('unique-bunchId', $extensions);
        $this->assertEquals('.', $photoBunch->directory());
        $this->assertEquals('unique-bunchId', $photoBunch->basename());
        $this->assertEquals($extensions, $photoBunch->extensions());

        $photoBunch = new FileBunch('/unique-bunchId', $extensions);
        $this->assertEquals('/', $photoBunch->directory());
        $this->assertEquals('unique-bunchId', $photoBunch->basename());
        $this->assertEquals($extensions, $photoBunch->extensions());

        $photoBunch = new FileBunch('/directory/unique-bunchId', $extensions);
        $this->assertEquals('/directory', $photoBunch->directory());
        $this->assertEquals('unique-bunchId', $photoBunch->basename());
        $this->assertEquals($extensions, $photoBunch->extensions());
    }

    public function test_photoExtensions()
    {
        $extensions = array_merge(
            Scanner::supportedExtensions(),
            array('not-photo-extension-1', 'not-photo-extension-2', 'not-photo-extension-3')
        );
        shuffle($extensions);

        $photoBunch = new FileBunch('unique-bunchId', $extensions);
        $this->assertEquals(Scanner::supportedExtensions(), $photoBunch->photoExtensions());
    }

    public function test_failure_on_empty_extensions()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Empty extensions list passed.',
            FileBunch::ERROR_EMPTY_EXTENSIONS
        );
        new FileBunch('unique-bunchId', array());
    }

    public function test_failure_when_no_photo_extensions()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'No supported photo-extensions passed.',
            FileBunch::ERROR_NO_PHOTO_EXTENSIONS
        );
        new FileBunch('unique-bunchId', array('unsupported-extension-1', 'unsupported-extension-2'));
    }

    public function test_exifs()
    {
        $photoBunch = new FileBunch($this->resourceDirectory() . '/cubie', array('jpg', 'xmp', 'txt'));
        $exifs = $photoBunch->exifs();
        $this->assertInternalType('array', $exifs);
        $this->assertCount(1, $exifs);
        $this->assertArrayHasKey('jpg', $exifs);
        $this->assertInstanceOf('\\Zebooka\\PD\\Exif', $exifs['jpg']);
    }
}
