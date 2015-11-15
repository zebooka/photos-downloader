<?php

namespace Zebooka\PD;

class Tokenizer
{
    private $configure;
    private $exifAnalyzer;

    public function  __construct(Configure $configure, ExifAnalyzer $exifAnalyzer)
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
        $prefix = $this->extractPrefix($tokens);
        list($datetime, $shot) = $this->extractDateTimeShot($tokens, $exifDateTime);
        if (null === $datetime) {
            throw new TokenizerException('Unable to detect date/time.', TokenizerException::NO_DATE_TIME_DETECTED);
        }
        $author = $this->extractAuthor($tokens);
        $camera = $this->extractCamera($tokens, $exifCamera);
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

    private function extractPrefix(array &$tokens)
    {
        $prefix = null;
        if (preg_match('/^[A-Z]{1}$/', reset($tokens))) {
            $prefix = array_shift($tokens);
        }
        $tokens = array_values($tokens);
        return $prefix;
    }

    private function extractAuthor(array &$tokens)
    {
        $author = null;
        foreach ($tokens as $index => $token) {
            if (in_array($token, $this->configure->knownAuthors())) {
                unset($tokens[$index]);
                $author = $token;
                break;
            }
        }
        if ('' === $this->configure->author) {
            $author = null;
        } elseif (is_string($this->configure->author)) {
            $author = $this->configure->author;
        }
        $tokens = array_values($tokens);
        return $author;
    }

    private function extractCamera(array &$tokens, $exifCamera)
    {
        $camera = ($exifCamera ? : null);
        foreach ($tokens as $index => $token) {
            if (in_array($token, $this->configure->knownCameras())) {
                unset($tokens[$index]);
                $camera = $token;
                break;
            }
        }
        $tokens = array_values($tokens);
        return $camera;
    }

    private function extractDateTimeShot(array &$tokens, $exifDateTime)
    {
        $datetime = (null !== $exifDateTime ? $exifDateTime : null);
        $shot = null;
        foreach ($tokens as $index => $token) {
            if (preg_match('/^([0-9]{2}[0-9]{2}[0-9]{2}|[0-9Y]{4}[0-9M]{2}[0-9D]{2})$/', $token)) {
                $datetime = array($token);
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
                break;
            } elseif (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})[ -]([0-9]{1,2})[\\.-]([0-9]{1,2})[\\.-]([0-9]{1,2})$/', $token, $matches)) {
                $datetime = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
                unset($tokens[$index]);
                break;
            } elseif (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $token, $matches) && isset($tokens[$index + 1])
                && preg_match('/^([0-9]{1,2})[\\.-]([0-9]{1,2})[\\.-]([0-9]{1,2})$/', $tokens[$index + 1], $matches2)
            ) {
                $datetime = mktime($matches2[1], $matches2[2], $matches2[3], $matches[2], $matches[3], $matches[1]);
                unset($tokens[$index]);
                unset($tokens[$index + 1]);
                break;
            } elseif (preg_match('/^([1-9][0-9Y]{3}x?)$/', $token, $matches) && isset($tokens[$index + 1])
                && preg_match('/^([0-9]+)$/', $tokens[$index + 1], $matches2)
            ) {
                $datetime = array($token);
                $shot = $tokens[$index + 1];
                unset($tokens[$index]);
                unset($tokens[$index + 1]);
            }
        }
        $tokens = array_values($tokens);

        if (null !== $exifDateTime && $this->configure->preferExifDateTime) {
            $datetime = $exifDateTime;
        }

        return array($datetime, $shot);
    }
}
