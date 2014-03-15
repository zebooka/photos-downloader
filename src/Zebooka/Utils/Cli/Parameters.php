<?php

namespace Zebooka\Utils\Cli;

class Parameters
{
    public function __construct(array $parameters)
    {
        foreach ($parameters as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Factory to build from $_SERVER['argv'].
     * @param array $argv Incoming parameters ($_SERVER['argv'])
     * @param array $reqvals Parameters that come with value
     * @param array $multiple Parameters that may come multiple times
     * @param array $aliases Aliases in form [$aliasKey => $originalKey]
     */
    public static function createFromArgv(array $argv, array $reqvals = array(), array $multiple = array(), array $aliases = array())
    {
        $parameters = array();
        reset($argv);
        while (list(, $p) = each($argv)) {
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
                $nextparam = current($argv);
                if ($value === true && in_array($pname, $reqvals)) {
                    if ($nextparam !== false) {
                        // next param is value
                        list(, $value) = each($argv);
                    } else {
                        // required value for option not found
                        $value = false;
                    }
                }
                if (in_array($pname, $multiple)) {
                    if (!isset($parameters[$pname])) {
                        $parameters[$pname] = array();
                    }
                    $parameters[$pname][] = $value;
                } else {
                    $parameters[$pname] = $value;
                }
            } else {
                if ($p == '--') {
                    // all next params are not parsed
                    while (list(, $p) = each($argv)) {
                        $parameters[] = $p;
                    }
                } else {
                    // param doesn't belong to any option
                    $parameters[] = $p;
                }
            }
        }
        return new self($parameters);
    }

    /**
     * Build array like $_SERVER['argv'] from current instance.
     * @param array $reqvals Parameters that come with value
     * @param array $multiple Parameters that may come multiple times
     */
    public function argv(array $reqvals = array(), array $multiple = array(), array $longOptions = array())
    {
        $argv = array();
        $positioned = array();
        foreach ($this as $name => $value) {
            if (is_numeric($name)) {
                $positioned[$name] = $value;
            } else {
                if (in_array($name, $longOptions)) {
                    $dashName = '--' . $name;
                } else {
                    $dashName = '-' . $name;
                }
                if (!in_array($name, $multiple)) {
                    $value = array($value);
                }
                foreach ($value as $subValue) {
                    if (false === $subValue || null === $subValue) {
                        continue;
                    }
                    if (in_array($name, $reqvals)) {
                        $argv[] = $dashName;
                        $argv[] = escapeshellarg(strval($subValue));
                    } else {
                        if ($subValue) {
                            $argv[] = $dashName;
                        }
                    }
                }
            }
        }
        if (isset($positioned[0])) {
            array_unshift($argv, escapeshellarg(strval($positioned[0])));
            unset($positioned[0]);
        }
        if (count($positioned) > 0) {
            $argv[] = '--';
            foreach ($positioned as $value) {
                $argv[] = escapeshellarg(strval($value));
            }
        }
        return $argv;
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
