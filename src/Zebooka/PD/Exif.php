<?php

namespace Zebooka\PD;

/**
 * @property string $SourceFile
 * @property string $FileName
 * @property string $FileModifyDate
 * @property string $MIMEType
 * @property string $Make
 * @property string $Model
 * @property string $DateTimeOriginal
 */
class Exif
{
    public function __construct($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException('File \'' . $filename . '\' not found or is not readable.');
        }

        $o = array();
        $code = 0;
        exec('exiftool -j -d "%Y-%m-%d %H:%M:%S" -fast ' . escapeshellarg($filename), $o, $code);
        if ($code) {
            throw new \RuntimeException('Exiftool failed with code #' . $code . '.');
        }
        $t = implode(PHP_EOL, $o);
        // replace invalid UTF-8 symbols
        $t = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $t
        );
        $t = json_decode($t, true);
        $exif = reset($t);
        foreach ($exif as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * Magic getter for not set properties. Always returns null.
     * @param $property
     * @return null
     */
    public function __get($property)
    {
        return null;
    }
}
