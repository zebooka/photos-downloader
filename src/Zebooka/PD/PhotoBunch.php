<?php

namespace Zebooka\PD;

class PhotoBunch
{
    const ERROR_EMPTY_EXTENSIONS = 1;
    const ERROR_NO_PHOTO_EXTENSIONS = 2;

    private $bunchId;
    private $extensions;

    public function __construct($bunchId, array $extensions)
    {
        if (!count($extensions)) {
            throw new \InvalidArgumentException('Empty extensions list passed.', self::ERROR_EMPTY_EXTENSIONS);
        }
        $this->bunchId = strval($bunchId);
        $this->extensions = array_values(array_unique($extensions));
        if (!count($this->photoExtensions())) {
            throw new \InvalidArgumentException('No supported photo-extensions passed.', self::ERROR_NO_PHOTO_EXTENSIONS);
        }
    }

    public function __toString()
    {
        return
            $this->bunchId . '.' .
            (count($this->extensions) > 1 ? '{' . implode(',', $this->extensions) . '}' : $this->extensions[0]);
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
        return $this->extensions;
    }

    /**
     * @return string[]
     */
    public function photoExtensions()
    {
        return Scanner::filterSupportedExtensions($this->extensions);
    }

    /**
     * @return Exif[]
     */
    public function exifs()
    {
        $extensions = $this->photoExtensions();
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
