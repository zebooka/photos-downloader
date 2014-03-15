<?php

namespace Zebooka\PD;

class PhotoBunchTest extends \PHPUnit_Framework_TestCase
{
    public function test_creation()
    {
        foreach (Scanner::supportedExtensions() as $photoExtension) {
            // test with not-photo-extension
            $extensions = array($photoExtension, 'not-photo-extension');
            $photoBunch = new PhotoBunch('unique-bunchId', $extensions);
            $this->assertEquals('unique-bunchId.{' . implode(',', $extensions) . '}', strval($photoBunch));

            // test with single extension
            $photoBunch = new PhotoBunch('unique-bunchId', array($photoExtension));
            $this->assertEquals('unique-bunchId.' . $photoExtension, strval($photoBunch));
        }
    }

    public function test_methods()
    {
        $extensions = Scanner::supportedExtensions();
        $photoBunch = new PhotoBunch('unique-bunchId', $extensions);
        $this->assertEquals('.', $photoBunch->directory());
        $this->assertEquals('unique-bunchId', $photoBunch->basename());
        $this->assertEquals($extensions, $photoBunch->extensions());

        $photoBunch = new PhotoBunch('/unique-bunchId', $extensions);
        $this->assertEquals('/', $photoBunch->directory());
        $this->assertEquals('unique-bunchId', $photoBunch->basename());
        $this->assertEquals($extensions, $photoBunch->extensions());

        $photoBunch = new PhotoBunch('/directory/unique-bunchId', $extensions);
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

        $photoBunch = new PhotoBunch('unique-bunchId', $extensions);
        $this->assertEquals(Scanner::supportedExtensions(), $photoBunch->photoExtensions());
    }

    public function test_failure_on_empty_extensions()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Empty extensions list passed.',
            PhotoBunch::ERROR_EMPTY_EXTENSIONS
        );
        new PhotoBunch('unique-bunchId', array());
    }

    public function test_failure_when_no_photo_extensions()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'No supported photo-extensions passed.',
            PhotoBunch::ERROR_NO_PHOTO_EXTENSIONS
        );
        new PhotoBunch('unique-bunchId', array('unsupported-extension-1', 'unsupported-extension-2'));
    }
}
