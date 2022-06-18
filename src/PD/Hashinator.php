<?php

namespace Zebooka\PD;

class Hashinator
{
    const ERROR_FILE_NOT_READABLE = 1;

    private $hashes = array();

    public function equal($filename1, $filename2)
    {
        if (!is_readable($filename1) || !is_readable($filename2)) {
            throw new \UnexpectedValueException(
                'One of compared file is not readable.',
                self::ERROR_FILE_NOT_READABLE
            );
        }

        if (filesize($filename1) !== filesize($filename2)) {
            return false;
        }

        if (!isset($this->hashes[$filename1])) {
            $this->hashes[$filename1] = $this->hash($filename1);
        }

        if (!isset($this->hashes[$filename2])) {
            $this->hashes[$filename2] = $this->hash($filename2);
        }

        return ($this->hashes[$filename1] === $this->hashes[$filename2]);
    }

    private function hash($filename)
    {
        return md5_file($filename);
    }
}
