<?php

namespace Zebooka\Translator;

class TranslatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/translator';
    }

    public function test_en_locale()
    {
        $translator = TranslatorFactory::translator($this->resourceDirectory(), 'en');
        $this->assertEquals('notTranslated', $translator->translate('notTranslated'));
        $this->assertRegExp('/^notTranslatedWithParams\\(.+\\)$/', $translator->translate('notTranslatedWithParams', array('abc', '123')));
        $this->assertEquals('This is a test', $translator->translate('test'));
        $this->assertEquals('Another test #2', $translator->translate('anotherTest', array(2)));
    }

    public function test_ru_locale()
    {
        $translator = TranslatorFactory::translator($this->resourceDirectory(), 'ru');
        $this->assertEquals('Это тест', $translator->translate('test'));
        $this->assertEquals('Другой тест №2', $translator->translate('anotherTest', array(2)));
    }


    public function test_en_locale_loaded_instead_of_unexisting()
    {
        $translator = TranslatorFactory::translator($this->resourceDirectory(), 'xx');
        $this->assertEquals('This is a test', $translator->translate('test'));
    }

    public function test_en_locale_loaded_instead_of_malformed()
    {
        $translator = TranslatorFactory::translator($this->resourceDirectory(), 'yy');
        $this->assertEquals('This is a test', $translator->translate('test'));
    }
}
