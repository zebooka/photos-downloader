<?php

namespace Zebooka\Utils;

class Size
{
    public static function humanReadableSize($bytes, $binary = false, $precision = 3)
    {
        if ($binary) {
            $block = 1024;
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        } else {
            $block = 1000;
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        }
        $pow = floor(($bytes ? log(abs($bytes)) : 0) / log($block));
        $pow = min($pow, count($units) - 1);
        $xBytes = $bytes / pow($block, $pow);
        return round($xBytes, $precision) . ' ' . $units[$pow];
    }
}
