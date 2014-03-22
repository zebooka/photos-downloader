<?php

namespace Zebooka\Translator;

class TranslatorFactory
{
    public static function translator($translationsDir, $locale)
    {
        if (preg_match('/^([a-z]{2})(_|$)/i', $locale, $matches)) {
            $locale = $matches[1];
        } else {
            $locale = 'en';
        }
        try {
            return self::createTranslator($translationsDir, $locale);
        } catch (\Exception $e) {
            return self::createTranslator($translationsDir, 'en');
        }
    }

    private static function createTranslator($translationsDir, $locale)
    {
        $translations = json_decode(file_get_contents($translationsDir . '/' . $locale . '.json'), true);
        if (null === $translations) {
            throw new \UnexpectedValueException('Unable to decode [' . $locale . '] translations file.');
        }
        return new Translator($translations, $locale);
    }
}
