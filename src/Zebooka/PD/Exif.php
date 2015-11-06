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
 * @property string $CreateDate
 * @property string $ModifyDate
 * @property string $GPSDateTime
 * @property string $CreationDate
 * @property string $TrackCreateDate
 * @property string $MediaCreateDate
 * @property string $Software
 * @property string $ImageWidth
 * @property string $ImageHeight
 */
class Exif
{
    public function __construct($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException('File \'' . $filename . '\' not found or is not readable.');
        }

        $output = array();
        $code = 0;
        // -d "%Y-%m-%d %H:%M:%S %z" - we no longer use this format because it will output current timezone
        // if date does not have one. But we need to distinguish between local tz and no tz.
        exec('exiftool -j -d "%Y-%m-%d %H:%M:%S %z" -fast ' . escapeshellarg($filename), $output, $code);
        if ($code) {
            throw new \RuntimeException('ExifTool failed with code #' . $code . '.');
        }
        // replace invalid UTF-8 symbols
        $data = json_decode(
            preg_replace(
                '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+' .
                '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
                '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
                '',
                implode(PHP_EOL, $output)
            ),
            true
        );
        if (null === $data) {
            throw new \UnexpectedValueException('Unable to decode ExifTool json output.');
        }
        $exif = reset($data);
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
