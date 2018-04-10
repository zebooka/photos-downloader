<?php

namespace Zebooka\PD;

class Tokenizer
{
    private $configure;
    private $exifAnalyzer;

    public function __construct(Configure $configure, ExifAnalyzer $exifAnalyzer)
    {
        $this->configure = $configure;
        $this->exifAnalyzer = $exifAnalyzer;
    }

    public function tokenize(FileBunch $fileBunch)
    {
        list($exifDateTime, $exifCamera, $exifTokens) = $this->exifAnalyzer->extractDateTimeCameraTokens($fileBunch);
        $tokens = array_values(
            array_filter(
                explode(Tokens::SEPARATOR, $fileBunch->basename()),
                function ($value) {
                    return '' !== $value;
                }
            )
        );
        $prefix = self::extractPrefix($tokens);
        list($datetime, $shot) = self::extractDateTimeShot($tokens, $exifDateTime, $this->configure->preferExifDateTime);
        if (null === $datetime) {
            throw new TokenizerException('Unable to detect date/time.', TokenizerException::NO_DATE_TIME_DETECTED);
        }
        $author = self::extractAuthor($tokens, $this->configure->knownAuthors(), $this->configure->author);
        $camera = self::extractCamera($tokens, $this->configure->knownCameras(), $exifCamera);
        if ($this->configure->tokensDropUnknown) {
            $tokens = array_intersect($tokens, $this->configure->knownTokens());
        }
        $tokens = array_merge($tokens, $exifTokens);
        $tokens = array_diff($tokens, $this->configure->tokensToDrop);
        $tokens = array_merge($tokens, $this->configure->tokensToAdd);
        $tokens = array_values(array_unique($tokens));
        $tokens = array_merge(array_intersect($tokens, $this->configure->knownTokens()), array_diff($tokens, $this->configure->knownTokens()));
        return new Tokens($datetime, $tokens, $author, $camera, $prefix, $shot);
    }

    public static function extractPrefix(array &$tokens)
    {
        $prefix = null;
        if (preg_match('/^[A-Z]{1}$/', reset($tokens))) {
            $prefix = array_shift($tokens);
        }
        $tokens = array_values($tokens);
        return $prefix;
    }

    public static function extractAuthor(array &$tokens, array $knownAuthors, $predefinedAuthor = null)
    {
        $author = null;
        foreach ($tokens as $index => $token) {
            if ((preg_match('/^[A-Z]{3}$/', $token) && !in_array($token, array('IMG', 'DSC'))) || in_array($token, $knownAuthors)) {
                unset($tokens[$index]);
                $author = $token;
                break;
            }
        }
        if ('' === $predefinedAuthor) {
            $author = null;
        } elseif (is_string($predefinedAuthor)) {
            $author = $predefinedAuthor;
        }
        $tokens = array_values($tokens);
        return $author;
    }

    public static function extractCamera(array &$tokens,  array $knownCameras, $exifCamera = null)
    {
        $camera = ($exifCamera ?: null);
        foreach ($tokens as $index => $token) {
            if (in_array($token, $knownCameras)) {
                unset($tokens[$index]);
                $camera = $token;
                break;
            }
        }
        $tokens = array_values($tokens);
        return $camera;
    }

    public static function extractDateTimeShot(array &$tokens, $exifDateTime, $preferExifDateTime = false)
    {
        $datetime = (null !== $exifDateTime ? $exifDateTime : null);
        $shot = null;
        foreach ($tokens as $index => $token) {
            $result = self::detectClassicDateTime($token, $index, $tokens)
                ?: self::detectDashedCombinedDateTime($token, $index, $tokens)
                    ?: self::detectDashedDateTime($token, $index, $tokens)
                        ?: self::detectSJCamDateShot($token, $index, $tokens)
                            ?: self::detectFilmDateShot($token, $index, $tokens);

            if ($result) {
                list($datetime, $shot) = $result;
                break;
            }
        }
        $tokens = array_values($tokens);

        if (null !== $exifDateTime && $preferExifDateTime) {
            $datetime = $exifDateTime;
        }

        return array($datetime, $shot);
    }

    public static function detectClassicDateTime($token, $index, array &$tokens)
    {
        if (preg_match('/^([0-9]{2}[0-9]{2}[0-9]{2}|[0-9Y]{4}[0-9M]{2}[0-9D]{2})$/', $token)) {
            $datetime = array($token);
            $shot = false;
            unset($tokens[$index]);
            if (isset($tokens[$index + 1])
                && preg_match('/^([0-9H]{2}[0-9M]{2}[0-9S]{2})(?:' . Tokens::TIME_SHOT_SEPARATOR . '([0-9]+))?$/', $tokens[$index + 1], $matches)
            ) {
                if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $token, $dateMatches) &&
                    preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/', $matches[1], $timeMatches)
                ) {
                    $datetime = mktime(
                        intval($timeMatches[1]),
                        intval($timeMatches[2]),
                        intval($timeMatches[3]),
                        intval($dateMatches[2]),
                        intval($dateMatches[3]),
                        intval($dateMatches[1])
                    );
                } elseif (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/', $token, $dateMatches) &&
                    preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/', $matches[1], $timeMatches)
                ) {
                    $datetime = mktime(
                        intval($timeMatches[1]),
                        intval($timeMatches[2]),
                        intval($timeMatches[3]),
                        intval($dateMatches[2]),
                        intval($dateMatches[3]),
                        2000 + intval($dateMatches[1])
                    );
                } else {
                    $datetime[] = $matches[1];
                }
                if (isset($matches[2]) && '' !== $matches[2]) {
                    $shot = $matches[2];
                } elseif (isset($tokens[$index + 2]) && preg_match('/^[0-9]+?$/', $tokens[$index + 2])) {
                    $shot = $tokens[$index + 2];
                    unset($tokens[$index + 2]);
                }
                unset($tokens[$index + 1]);
            } elseif (isset($tokens[$index + 1]) && preg_match('/^[0-9]+?$/', $tokens[$index + 1])) {
                $shot = $tokens[$index + 1];
                unset($tokens[$index + 1]);
            }
            return array($datetime, $shot);
        }
        return false;
    }

    public static function detectDashedCombinedDateTime($token, $index, array &$tokens)
    {
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})[ -]([0-9]{1,2})[\\.-]([0-9]{1,2})[\\.-]([0-9]{1,2})$/', $token, $matches)) {
            $datetime = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            unset($tokens[$index]);
            return array($datetime, null);
        }
        return false;
    }

    public static function detectDashedDateTime($token, $index, array &$tokens)
    {
        // YYYY-MM-DD_HH-MM-SS + YYYY-MM-DD_HH.MM.SS
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $token, $matches) && isset($tokens[$index + 1])
            && preg_match('/^([0-9]{1,2})[\\.-]([0-9]{1,2})[\\.-]([0-9]{1,2})$/', $tokens[$index + 1], $matches2)
        ) {
            $datetime = mktime($matches2[1], $matches2[2], $matches2[3], $matches[2], $matches[3], $matches[1]);
            unset($tokens[$index]);
            unset($tokens[$index + 1]);
            return array($datetime, null);
        }
        return false;
    }

    public static function detectSJCamDateShot($token, $index, array &$tokens)
    {
        // YYYY_MMDD_HHMMSS_SHOT
        if (preg_match('/^([0-9]{4})$/', $token, $matches)
            && isset($tokens[$index + 1]) && preg_match('/^([0-9]{2})([0-9]{2})$/', $tokens[$index + 1], $matches2)
            && isset($tokens[$index + 2]) && preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/', $tokens[$index + 2], $matches3)
        ) {
            $datetime = mktime($matches3[1], $matches3[2], $matches3[3], $matches2[1], $matches2[2], $matches[1]);
            unset($tokens[$index]);
            unset($tokens[$index + 1]);
            unset($tokens[$index + 2]);
            if (isset($tokens[$index + 3]) && preg_match('/^[0-9]+?$/', $tokens[$index + 3])) {
                $shot = $tokens[$index + 3];
                unset($tokens[$index + 3]);
            } else {
                $shot = null;
            }
            return array($datetime, $shot);
        }
        return false;
    }

    public static function detectFilmDateShot($token, $index, array &$tokens)
    {
        if (preg_match('/^([1-9][0-9Y]{3}x?)$/', $token, $matches) && isset($tokens[$index + 1])
            && preg_match('/^([0-9]+)$/', $tokens[$index + 1], $matches2)
        ) {
            $datetime = array($token);
            $shot = $tokens[$index + 1];
            unset($tokens[$index]);
            unset($tokens[$index + 1]);
            return array($datetime, $shot);
        }
        return false;
    }
}
