<?php

namespace Zebooka\PD;

class ExifAnalyzerException extends \RuntimeException
{
    const DIFFERENT_DATES = 1;
    const DIFFERENT_CAMERAS = 2;
}
