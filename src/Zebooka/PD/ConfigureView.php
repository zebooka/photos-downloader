<?php

namespace Zebooka\PD;

use Zebooka\Translator\Translator;

class ConfigureView
{
    private $configure;
    private $translator;
    private $screenWidth;

    public function __construct(Configure $configure, Translator $translator, $screenWidth = 80)
    {
        $this->configure = $configure;
        $this->translator = $translator;
        $this->screenWidth = $screenWidth;
    }

    public function render()
    {
        return
            PHP_EOL .
            $this->usage() .
            PHP_EOL . PHP_EOL .
            $this->parameters() .
            PHP_EOL . PHP_EOL .
            $this->currentConfiguration() .
            PHP_EOL;
    }

    private function indent()
    {
        return '   ';
    }

    private function usage()
    {
        return
            $this->translator->translate('usage') . PHP_EOL .
            $this->indent() . implode(
                ' ',
                array(
                    escapeshellarg($this->configure->executableName),
                    '[' . $this->translator->translate('usage/parameters') . ']',
                    '-' . Configure::P_FROM,
                    $this->translator->translate('usage/parameterValue/from'),
                    '[-' . Configure::P_FROM,
                    $this->translator->translate('usage/parameterValue/from'),
                    '...]',
                    '-' . Configure::P_TO,
                    $this->translator->translate('usage/parameterValue/to'),
                )
            );
    }

    private function parameters()
    {
        return
            $this->translator->translate('parameters') . PHP_EOL .
            $this->combineParametersDescriptions($this->extractParametersWithDescriptions());
    }

    private function currentConfiguration()
    {
        return
            $this->translator->translate('currentConfiguration') . PHP_EOL .
            $this->indent() . implode(' ', $this->configure->argv());
    }

    private function extractParametersWithDescriptions()
    {
        $configureClass = get_class($this->configure);
        $reflection = new \ReflectionClass($configureClass);
        $constants = array_filter(
            array_keys($reflection->getConstants()),
            function ($constant) {
                return preg_match('/^P_/', $constant);
            }
        );
        $translator = $this->translator;
        $parameters = array_reduce(
            $constants,
            function (&$parameters, $constant) use ($configureClass, $translator) {
                $constantValue = constant($configureClass . '::' . $constant);
                $parameter = '-' . $constantValue;
                if (in_array($constantValue, Configure::parametersRequiringValues())) {
                    $parameter .= ' ' . $translator->translate('parameters/value/' . $constant);
                }
                $parameters[$parameter] = $translator->translate('parameters/description/' . $constant);
                return $parameters;
            },
            array()
        );
        return $parameters;
    }

    private function combineParametersDescriptions(array $parameters)
    {
        $parameterMaxWidth = array_reduce(
            array_keys($parameters),
            function (&$max, $parameter) {
                return max($max, mb_strlen($parameter));
            },
            0
        );
        $descriptionMaxWidth = max($parameterMaxWidth, $this->screenWidth - 2 * mb_strlen($this->indent()) - $parameterMaxWidth);
        $keys = $this->wrapAndPadList(array_keys($parameters), $parameterMaxWidth);
        $descriptions = $this->wrapAndPadList(array_values($parameters), $descriptionMaxWidth);
        return $this->mergeTwoPaddedLists(
            $keys,
            $descriptions,
            str_repeat(' ', $parameterMaxWidth),
            str_repeat(' ', $descriptionMaxWidth)
        );
    }

    private function wrapAndPadList(array $list, $width)
    {
        return array_map(
            function ($line) use ($width) {
                $wrapAsArray = function ($string, $width) {
                    $words = mb_split('\\s', $string);
                    $result = array();
                    $line = '';
                    while (count($words) > 0) {
                        $word = array_shift($words);
                        if ('' !== $line && mb_strlen($line) + mb_strlen($word) > $width) {
                            $result[] = trim($line);
                            $line = '';
                        }
                        $line .= $word . ' ';
                    }
                    if ('' !== $line) {
                        $result[] = trim($line);
                    }
                    return $result;
                };
                return array_map(
                    function ($subline) use ($width) {
                        while (mb_strlen($subline) < $width) {
                            $subline .= ' ';
                        }
                        return $subline;
                    },
                    $wrapAsArray($line, $width)
                );
            },
            $list
        );
    }


    private function mergeTwoPaddedLists(array $a, array $b, $aPadString, $bPadString)
    {
        $indent = $this->indent();
        return implode(
            PHP_EOL,
            array_map(
                function ($a, $b) use ($indent, $aPadString, $bPadString) {
                    $lines = max(count($a), count($b));
                    while (count($a) < $lines) {
                        $a[] = $aPadString;
                    }
                    while (count($b) < $lines) {
                        $b[] = $bPadString;
                    }
                    return rtrim(
                        implode(
                            PHP_EOL,
                            array_map(
                                function ($a, $b) use ($indent) {
                                    return $indent . $a . $indent . $b;
                                },
                                $a,
                                $b
                            )
                        )
                    );

                },
                $a,
                $b
            )
        );
    }
}
