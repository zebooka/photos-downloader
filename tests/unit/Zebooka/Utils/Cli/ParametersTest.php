<?php

namespace Zebooka\Utils\Cli;

class ParametersTest extends \PHPUnit_Framework_TestCase
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
        $params = new Parameters($argv, $reqvals, $multiple, $aliases);
        $this->assertNull($params->unknown1);
        $this->assertTrue($params->short1);
        $this->assertTrue($params->long2);
        $this->assertEquals('value3', $params->short3);
        $this->assertEquals(array('value4a', 'value4b', 'value4c'), $params->short4);
        $this->assertEquals('/example/bin', $params->{0});
        $this->assertEquals('positioned1', $params->{1});
        $this->assertEquals('-notparsed2', $params->{2});
        $this->assertEquals('--notparsed3', $params->{3});
    }

    public function test_reqval_param_without_value()
    {
        $params = new Parameters(array('-param1'), array('param1'));
        $this->assertFalse($params->param1);
    }

    public function test_multiple_param_always_array()
    {
        $params = new Parameters(array('-param1', 'value1'), array('param1'), array('param1'));
        $this->assertInternalType('array', $params->param1);
        $this->assertEquals(array('value1'), $params->param1);
    }

    public function test_multiple_param_without_values()
    {
        $params = new Parameters(array('-param1', '-param1'), array(), array('param1'));
        $this->assertInternalType('array', $params->param1);
        $this->assertCount(2, $params->param1);
        $this->assertEquals(array(true, true), $params->param1);
    }
}
