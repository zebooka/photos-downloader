<?php

namespace Zebooka\Translator;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_en_icu_translations()
    {
        $translator = new Translator(
            array('pluralTest' => 'We have {0, plural, one {# test} other {# tests}}',),
            'en'
        );
        $this->assertEquals('We have 1 test', $translator->translate('pluralTest', array(1)));
        $this->assertEquals('We have 2 tests', $translator->translate('pluralTest', array(2)));
        $this->assertEquals('We have 10 tests', $translator->translate('pluralTest', array(10)));
        $this->assertEquals('We have 100 tests', $translator->translate('pluralTest', array(100)));
    }

    public function test_ru_icu_translations()
    {
        $translator = new Translator(
            array('pluralTest' => 'У нас есть {0, plural, one {# тест} many {# тестов} other {# теста}}',),
            'ru'
        );
        $this->assertEquals('У нас есть 1 тест', $translator->translate('pluralTest', array(1)));
        $this->assertEquals('У нас есть 2 теста', $translator->translate('pluralTest', array(2)));
        $this->assertEquals('У нас есть 3 теста', $translator->translate('pluralTest', array(3)));
        $this->assertEquals('У нас есть 4 теста', $translator->translate('pluralTest', array(4)));
        $this->assertEquals('У нас есть 5 тестов', $translator->translate('pluralTest', array(5)));
        $this->assertEquals('У нас есть 11 тестов', $translator->translate('pluralTest', array(11)));
        $this->assertEquals('У нас есть 12 тестов', $translator->translate('pluralTest', array(12)));
        $this->assertEquals('У нас есть 13 тестов', $translator->translate('pluralTest', array(13)));
        $this->assertEquals('У нас есть 14 тестов', $translator->translate('pluralTest', array(14)));
        $this->assertEquals('У нас есть 21 тест', $translator->translate('pluralTest', array(21)));
        $this->assertEquals('У нас есть 22 теста', $translator->translate('pluralTest', array(22)));
        $this->assertEquals('У нас есть 23 теста', $translator->translate('pluralTest', array(23)));
        $this->assertEquals('У нас есть 24 теста', $translator->translate('pluralTest', array(24)));
        $this->assertEquals('У нас есть 25 тестов', $translator->translate('pluralTest', array(25)));
    }

    public function test_failure_on_malformed_icu_string()
    {
        $this->setExpectedException('\\RuntimeException', 'ICU translation creation failed.', Translator::ERROR_ICU_CREATION_FAILED);
        $translator = new Translator(
            array('badTranslation' => 'We have {0, plural, one {# bad translation'),
            'en'
        );
        $translator->translate('badTranslation', array(0));
    }
}
