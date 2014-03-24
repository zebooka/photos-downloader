<?php

namespace Zebooka\PD;

class Tokenizer
{
    private $configure;

    public function  __construct(Configure $configure)
    {
        // TODO: add ExifAnalyzer - to compare exifs and get date/time/shot/camera from it
        $this->configure = $configure;
    }

    public function tokenize(PhotoBunch $photoBunch)
    {
        $tokens = array_values(
            array_filter(
                explode(Tokens::SEPARATOR, $photoBunch->basename()),
                function ($value) {
                    return '' !== $value;
                }
            )
        );
        $prefix = $this->extractPrefix($tokens);
        list($datetime, $shot) = $this->extractDateTimeShot($tokens);
        $author = $this->extractAuthor($tokens);
        $camera = $this->extractCamera($tokens);
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

    private function extractCamera(array &$tokens)
    {
        $camera = null;
        // TODO: detect from Exif
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

    private function extractDateTimeShot(array &$tokens)
    {
        $datetime = $shot = null;
        // TODO: detect date/time/shot from Exif
        foreach ($tokens as $index => $token) {
            if (preg_match('/^[0-9Y]{2}[0-9M]{2}[0-9D]{2}$/', $token)) {
                $datetime = array($token);
                unset($tokens[$index]);
                if (isset($tokens[$index + 1]) && preg_match('/^([0-9]{6})(?:,([0-9]+))?$/', $tokens[$index + 1], $matches)) {
                    $datetime[] = $matches[1];
                    if (isset($matches[2]) && '' !== $matches[2]) {
                        $shot = $matches[2];
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
            }
            // TODO: implement special flag to take shot number from DCIM/IMGP1234 basename

        }
        $tokens = array_values($tokens);
        return array($datetime, $shot);
    }
}
