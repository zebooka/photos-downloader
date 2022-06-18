<?php

namespace Zebooka\Translator;

use PHPUnit\Framework\TestCase;

class TranslationsInAllLanguagesTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../../res';
    }

    private function localesFiles()
    {
        return array_filter(
            glob($this->resourceDirectory() . '/*.json'),
            function ($filename) {
                return preg_match('/^([a-z]{2}(_[A-Z]{2})?)$/i', basename($filename, '.json'));
            }
        );
    }

    private function locales()
    {
        return array_map(
            function ($filename) {
                return basename($filename, '.json');
            },
            $this->localesFiles()
        );
    }


    private function hashes()
    {
        $keys = [];
        foreach ($this->localesFiles() as $filename) {
            $data = json_decode(file_get_contents($filename), true);
            $keys = array_merge($keys, array_keys($data));
        }

        return array_unique($keys);
    }

    public function test_all_locales_hashes()
    {
        $hashes = $this->hashes();
        foreach ($this->locales() as $locale) {
            $translator = TranslatorFactory::translator($this->resourceDirectory(), $locale);
            foreach ($hashes as $hash)
            $this->assertStringNotContainsString($hash, $translator->translate($hash));
        }
    }
}
