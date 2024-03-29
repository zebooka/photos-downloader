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
 * @property string $OffsetTime
 * @property string $OffsetTimeOriginal
 * @property string $OffsetTimeDigitized
 * @property string $ModifyDate
 * @property string $GPSDateTime
 * @property string $SubSecCreateDate
 * @property string $SubSecDateTimeOriginal
 * @property string $SubSecModifyDate
 * @property string $CreationDate
 * @property string $TrackCreateDate
 * @property string $MediaCreateDate
 * @property string $Software
 * @property string $ImageWidth
 * @property string $ImageHeight
 */
class Exif
{
    private $data = [];

    /**
     * @param string|array $filenameOrExif1
     * @param null|array $exif2
     */
    public function __construct($filenameOrExif1, $exif2 = null)
    {
        $exif0 = [];
        if (is_array($filenameOrExif1)) {
            $exif1 = $filenameOrExif1;
        } else {
            if (!is_file($filenameOrExif1) || !is_readable($filenameOrExif1)) {
                throw new \InvalidArgumentException('File \'' . $filenameOrExif1 . '\' not found or is not readable.');
            }
            $exif1 = $this->readExif($filenameOrExif1, '');
            $exif2 = $this->readExif($filenameOrExif1, '-d "%Y-%m-%d %H:%M:%S.%f %z"');
        }

        foreach ($exif1 as $key => $value) {
            if ('0000:00:00 00:00:00' == $value) {
                // nothing
            } elseif (isset($exif2[$key]) && $exif1[$key] !== $exif2[$key]) {
                $exif0[$key] = self::decodeDateTime($exif1[$key]) ?: self::decodeDateTime($exif2[$key]);
            } else {
                $exif0[$key] = $value;
            }
        }

        foreach ($exif0 as $property => $value) {
            $this->{$property} = $value;
        }
    }

    private function readExif($filename, $flags)
    {
        $output = array();
        $code = 0;
        // -d "%Y-%m-%d %H:%M:%S %z" - we no longer use this format because it will output current timezone
        // if date does not have one. But we need to distinguish between local tz and no tz.
        exec("exiftool -j -fast {$flags} " . escapeshellarg($filename), $output, $code);
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

        return $exif;
    }

    private static function decodeDateTime($mixed)
    {
        if (!is_string($mixed)) {
            return null;
        } elseif (preg_match('/^(\d+)[:\\.-](\d+)[:\\.-](\d+) (\d+)[:\\.-](\d+)[:\\.-](\d+)(?:\\.(\d+))?([+-]\d+:?\d*)?$/', $mixed, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}" . (!empty($m[7]) ? ".{$m[7]}" : '') . (!empty($m[8]) ? " {$m[8]}" : '');
        } elseif (preg_match('/([+-][0-9]{1,2}:?([0-9]{1,2})?)$/', $mixed, $m) && $tms = strtotime($mixed)) {
            return date('Y-m-d H:i:s O', $tms);
        } elseif ($tms = strtotime($mixed)) {
            return date('Y-m-d H:i:s', $tms);
        } else {
            return null;
        }
        // 2019:11:08 12:41:43.259+07:00
    }

    public function __get($property)
    {
        return $this->data[$property] ?? null;
    }

    public function __set($property, $value)
    {
        $this->data[$property] = $value;
    }

    public function __isset($property)
    {
        return isset($this->data[$property]);
    }

    public function __unset($property)
    {
        unset($this->data[$property]);
    }
}
