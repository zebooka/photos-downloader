<?php

namespace Zebooka\Utils\Cli;

use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function test_parameters_parsed()
    {
        $argv = array(
            0 => '/example/bin',
            '-short1',
            '--long2',
            '-short3',
            'value3',
            '--long4',
            'value4a',
            'positioned1',
            '-short4',
            'value4b',
            '--long4=value4c',
            '--',
            '-notparsed2',
            '--notparsed3',
        );
        $reqvals = array('short3', 'short4');
        $multiple = array('short4');
        $aliases = array('long4' => 'short4');
        $params = Parameters::createFromArgv($argv, $reqvals, $multiple, $aliases);
        $this->assertNull($params->unknown1);
        $this->assertTrue($params->short1);
        $this->assertTrue($params->long2);
        $this->assertEquals('value3', $params->short3);
        $this->assertEquals(array('value4a', 'value4b', 'value4c'), $params->short4);
        $this->assertEquals('/example/bin', $params->{0});
        $this->assertEquals('positioned1', $params->{1});
        $this->assertEquals('-notparsed2', $params->{2});
        $this->assertEquals('--notparsed3', $params->{3});
        $longOpts = array('long2', 'long4');
        $argv2 = $params->argv($reqvals, $multiple, $longOpts);
        $assertValue = array(
            0 => escapeshellarg('/example/bin'),
            '-short1',
            '--long2',
            '-short3',
            escapeshellarg('value3'),
            '-short4',
            escapeshellarg('value4a'),
            '-short4',
            escapeshellarg('value4b'),
            '-short4',
            escapeshellarg('value4c'),
            '--',
            escapeshellarg('positioned1'),
            escapeshellarg('-notparsed2'),
            escapeshellarg('--notparsed3'),
        );
        $this->assertEquals($assertValue, $argv2);
    }

    public function test_reqval_param_without_value()
    {
        $reqvals = array('param1');
        $params = Parameters::createFromArgv(array('-param1'), $reqvals);
        $this->assertFalse($params->param1);
        $this->assertEquals(array(), $params->argv($reqvals));
    }

    public function test_multiple_param_always_array()
    {
        $reqvals = $multiple = array('param1');
        $params = Parameters::createFromArgv(array('-param1', 'value1'), $reqvals, $multiple);
        $this->assertInternalType('array', $params->param1);
        $this->assertEquals(array('value1'), $params->param1);
        $this->assertEquals(array('-param1', escapeshellarg('value1')), $params->argv($reqvals, $multiple));
    }

    public function test_multiple_param_without_values()
    {
        $multiple = array('param1');
        $params = Parameters::createFromArgv(array('-param1', '-param1'), array(), $multiple);
        $this->assertInternalType('array', $params->param1);
        $this->assertCount(2, $params->param1);
        $this->assertEquals(array(true, true), $params->param1);
        $this->assertEquals(array('-param1', '-param1'), $params->argv(array(), $multiple));
    }
}
