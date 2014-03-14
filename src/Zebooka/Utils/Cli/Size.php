<?php

namespace Zebooka\Utils\Cli;

class Size
{
    public static function getTerminalWidth()
    {
        $code = 0;
        $output = array();
        exec('stty -a | grep columns', $output, $code);
        if ($code) {
            return false;
        }
        if (!preg_match('/(?:^|[^0-9])([0-9]+) columns/', implode(' ', $output), $matches)) {
            return false;
        }
        return intval($matches[1]) ?: false;
    }
}
