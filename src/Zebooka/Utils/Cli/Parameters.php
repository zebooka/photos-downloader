<?php

namespace Zebooka\Utils\Cli;

class Parameters
{
    /**
     * @param array $params Incoming parameters ($_SERVER['argv'])
     * @param array $reqvals Parameters that come with value
     * @param array $multiple Parameters that may come multiple times
     * @param array $aliases Aliases in form [$aliasKey => $originalKey]
     */
    public function __construct(array $params, array $reqvals = array(), array $multiple = array(), array $aliases = array())
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
                if (array_key_exists($pname, $aliases)) {
                    // replace alias with original name
                    $pname = $aliases[$pname];
                }
                $nextparam = current($params);
                if ($value === true && in_array($pname, $reqvals)) {
                    if ($nextparam !== false) {
                        // next param is value
                        list(, $value) = each($params);
                    } else {
                        // required value for option not found
                        $value = false;
                    }
                }
                if (in_array($pname, $multiple)) {
                    if (!isset($result[$pname])) {
                        $result[$pname] = array();
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
        foreach ($result as $pname => $value) {
            $this->{$pname} = $value;
        }
    }

    /**
     * Filter and return only positioned parameters
     * @param array $params
     */
    public function positionedParameters()
    {
        $positioned = array();
        foreach ($this as $name => $value) {
            if (is_numeric($name)) {
                $positioned[$name] = $value;
            }
        }
        return $positioned;
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
}
