<?php

namespace Zebooka\PD;

class FileBunch
{
    const ERROR_EMPTY_EXTENSIONS = 1;

    private $bunchId;
    private $primaryExtensions;
    private $secondaryExtensions;

    public function __construct($bunchId, array $primaryExtensions, array $secondaryExtensions = array())
    {
        if (!count($primaryExtensions)) {
            throw new \InvalidArgumentException('Empty primaryExtensions list passed.', self::ERROR_EMPTY_EXTENSIONS);
        }
        $this->bunchId = strval($bunchId);
        $this->primaryExtensions = Scanner::sortExtensions(array_unique($primaryExtensions));
        $this->secondaryExtensions = array_values(array_diff(array_unique($secondaryExtensions), $this->primaryExtensions));
    }

    public function __toString()
    {
        $extensions = $this->extensions();
        return
            $this->bunchId . '.' .
            (count($extensions) > 1 ? '{' . implode(',', $extensions) . '}' : reset($extensions));
    }

    public function directory()
    {
        return dirname($this->bunchId);
    }

    public function basename()
    {
        return basename($this->bunchId);
    }

    public function bunchId()
    {
        return $this->bunchId;
    }

    /**
     * @return string[]
     */
    public function extensions()
    {
        return array_merge($this->primaryExtensions, $this->secondaryExtensions);
    }

    /**
     * @return string[]
     */
    public function primaryExtensions()
    {
        return $this->primaryExtensions;
    }

    /**
     * @return string[]
     */
    public function secondaryExtensions()
    {
        return $this->secondaryExtensions;
    }

    /**
     * @return Exif[]
     */
    public function exifs()
    {
        $extensions = $this->primaryExtensions();
        $bunchId = $this->bunchId();
        return array_combine(
            $extensions,
            array_map(
                function ($extension) use ($bunchId) {
                    return new Exif($bunchId . '.' . $extension);
                },
                $extensions
            )
        );
    }
}
