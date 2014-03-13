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
    public static function parseParameters(array $params, array $reqvals = array(), array $multiple = array())
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
    public static function getPositionedParameters(array $params)
    {
        $positioned = array();
        foreach ($params as $name => $value) {
            if (is_int($name)) {
                $positioned[$name] = $value;
            }
        }
        return $positioned;
    }
}
