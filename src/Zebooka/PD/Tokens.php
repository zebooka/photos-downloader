<?php

namespace Zebooka\PD;

/**
 * @property string $prefix
 * @property int $shot
 * @property string $author
 * @property string $camera
 * @property array $tokens
 */
class Tokens
{
    const SEPARATOR = '_';
    const DATE_FORMAT = 'ymd';
    const TIME_FORMAT = 'His';
    const ERROR_NO_DATE_TIME = 1;

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
        $this->author = strval($author) ? : null;
        $this->camera = strval($camera) ? : null;
        $this->prefix = strval($prefix) ? : null;
        $this->shot = intval($shot) ? : null;
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
}
