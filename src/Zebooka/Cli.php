<?php

namespace Zebooka;

class Cli
{
    /**
     * Parse incoming parameters like from $_SERVER['argv'] array.
     * @param array $params Incoming parameters
     * @param array $reqvals Parameters with required value
     * @param array $multiple Parameters that may come multiple times
     * @return array
     */
    static public function parseParameters(array $params, array $reqvals = array(), array $multiple = array())
    {
        $result = array();
        reset($params);
        while (list(, $p) = each($params)) {
            if ($p[0] == '-' && $p != '-' && $p != '--') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname[0] == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                $nextparam = current($params);
                if ($value === true && in_array($pname, $reqvals)) {
                    if ($nextparam !== false) {
                        list(, $value) = each($params);
                    } else {
                        $value = false;
                    } // required value for option not found
                }
                if (in_array($pname, $multiple) && isset($result[$pname])) {
                    if (!is_array($result[$pname])) {
                        $result[$pname] = array($result[$pname]);
                    }
                    $result[$pname][] = $value;
                } else {
                    $result[$pname] = $value;
                }
            } else {
                if ($p == '--') {
                    // all next params are not parsed
                    while (list(, $p) = each($params)) {
                        $result[] = $p;
                    }
                } else {
                    // param doesn't belong to any option
                    $result[] = $p;
                }
            }
        }
        return $result;
    }

    /**
     * Filter and return only positioned parameters
     * @param array $params
     */
    static public function getPositionedParameters(array $params)
    {
        $positioned = array();
        foreach ($params as $name => $value) {
            if (is_int($name)) {
                $positioned[$name] = $value;
            }
        }
        return $positioned;
    }

    static public function humanReadableSize($bytes, $binary = false, $precision = 3, $space = ' ')
    {
        if ($binary) {
            $block = 1024;
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        } else {
            $block = 1000;
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        }
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log($block));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow($block, $pow);
        return round($bytes, $precision) . $space . $units[$pow];
    }

    static public function humanReadableTime($time)
    {
        $data = array(
            'day(s)' => intval(($time / 86400)),
            'hour(s)' => intval(($time / 3600) % 24),
            'minute(s)' => intval(($time / 60) % 60),
            'second(s)' => sprintf('%01.2F', ($time % 60) + ($time - intval($time))),
        );
        $data['second(s)'] = rtrim(rtrim($data['second(s)'], '0'), '.');
        $result = array();
        foreach ($data as $name => $value) {
            if ($value) {
                $result[] = $value . ' ' . $name;
            }
        }
        if (!count($result)) {
            $result[] = 'instantly';
        }
        return implode(' ', $result);
    }
}
