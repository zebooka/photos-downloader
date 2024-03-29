<?php

namespace Zebooka\PD;

use function PHP81_BC\strftime;

/**
 * @property string $prefix
 * @property int|string $shot
 * @property string $author
 * @property string $camera
 * @property array $tokens
 */
class Tokens
{
    const SEPARATOR = '_';
    const TIME_SHOT_SEPARATOR = ',';
    const DATE_FORMAT = 'ymd';
    const TIME_FORMAT = 'His';
    const ERROR_NO_DATE_TIME = 1;

    public $prefix;
    public $shot;
    public $author;
    public $camera;
    public $tokens;

    private $timestamp = null;
    private $date = null;
    private $time = null;

    /**
     * @param string|null $prefix
     * @param int|string|array|\DateTime $datetime
     * @param int|null $shot
     * @param string|null $author
     * @param string|null $camera
     * @param array $tokens
     */
    public function __construct(
        $datetime,
        array $tokens = array(),
        $author = null,
        $camera = null,
        $prefix = null,
        $shot = null
    ) {
        if (is_numeric($datetime)) {
            $this->timestamp = intval($datetime);
        } elseif (is_string($datetime)) {
            $this->timestamp = strtotime($datetime);
        } elseif (is_array($datetime) && count($datetime)) {
            $this->date = array_shift($datetime);
            if (count($datetime)) {
                $this->time = array_shift($datetime);
            }
        } elseif ($datetime instanceof \DateTime) {
            $this->timestamp = $datetime->getTimestamp();
        } else {
            throw new \InvalidArgumentException('Date/time parameter is invalid.', self::ERROR_NO_DATE_TIME);
        }

        $this->tokens = $tokens;
        $this->author = strval($author) ?: null;
        $this->camera = strval($camera) ?: null;
        $this->prefix = strval($prefix) ?: null;
        if (intval($shot) . '' === strval($shot)) {
            $this->shot = intval($shot);
        } else {
            $this->shot = strval($shot) ?: null;
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

    public function date()
    {
        return (isset($this->timestamp) ? date(self::DATE_FORMAT, $this->timestamp) : $this->date);
    }

    public function time()
    {
        return (isset($this->timestamp) ? date(self::TIME_FORMAT, $this->timestamp) : $this->time);
    }

    public function timestamp()
    {
        return (isset($this->timestamp) ? $this->timestamp : null);
    }

    public function increaseShot()
    {
        if (null === $this->shot) {
            $this->shot = 2;
        } else {
            $this->shot++;
        }
    }

    public function assembleBasename()
    {
        $filterNull = function ($v) {
            return (null !== $v && '' !== $v);
        };
        $basename = implode(
            self::SEPARATOR,
            array_filter(
                array(
                    $this->prefix,
                    $this->date(),
                    $this->time(),
                    $this->shot,
                    $this->author,
                    $this->camera,
                    implode(self::SEPARATOR, $this->tokens ?: array()),
                ),
                $filterNull
            )
        );
        return $basename;
    }

    public function assembleDirectory(Configure $configure)
    {
        $dir = null;
        if (null !== $this->timestamp()) {
            $dir = @strftime($configure->subDirectoriesFormat, $this->timestamp())
                ?: @strftime('%Y/%m', $this->timestamp());
        } elseif ($date = $this->date()) {
            if (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/i', $date, $matches)) {
                $year = 2000 + intval(ltrim($matches[1], '0'));
                $dir = @strftime($configure->subDirectoriesFormat, mktime(0, 0, 0, $matches[2], $matches[3], $year))
                    ?: $year . DIRECTORY_SEPARATOR . $matches[2];
            } elseif (preg_match('/^([0-9Y]{4})([0-9M]{2})([0-9D]{2})$/i', $date, $matches)) {
                $dir = $matches[1];
            } elseif (preg_match('/^([0-9Y]{4}x)?$/i', $date, $matches)) {
                $dir = $matches[1];
            }
        }
        return $dir;
    }
}
