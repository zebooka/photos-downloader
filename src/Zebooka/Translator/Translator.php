<?php

namespace Zebooka\Translator;

class Translator
{
    const ERROR_ICU_CREATION_FAILED = 1;
    const ERROR_ICU_TRANSLATION = 2;

    /**
     * @var string[]
     */
    private $translations;

    /**
     * @var \MessageFormatter[]
     */
    private $icuTranslations;

    private $locale;

    public function __construct(array $translations, $locale)
    {
        $this->translations = $translations;
        $this->icuTranslations = array();
        $this->locale = $locale;
    }

    public function translate($hash, $parameters = array())
    {
        if (array_key_exists($hash, $this->translations)) {
            return $this->icuFormatTranslation($hash, $parameters);
        } else {
            return $hash . ($parameters ? '(' . json_encode($parameters) . ')' : '');
        }
    }

    private function icuFormatTranslation($hash, $parameters)
    {
        if ($this->translations[$hash] == '') {
            return '';
        }
        if (!isset($this->icuTranslations[$hash])) {
            $icuTranslation = new \MessageFormatter($this->locale, $this->translations[$hash]);
            if (!$icuTranslation) {
                throw new \RuntimeException('ICU translation creation failed.', self::ERROR_ICU_CREATION_FAILED);
            }
            $this->icuTranslations[$hash] = $icuTranslation;
        }
        $translation = $this->icuTranslations[$hash]->format($parameters);
        if (false === $translation) {
            throw new \RuntimeException(
                'ICU translation error #' . $this->icuTranslations[$hash]->getErrorCode() . ' - ' . $this->icuTranslations[$hash]->getErrorMessage(),
                self::ERROR_ICU_TRANSLATION
            );
        }
        return $translation;
    }
}
