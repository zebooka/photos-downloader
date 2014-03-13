<?php

namespace Zebooka\PD;

class ConfigureTest extends \PHPUnit_Framework_TestCase
{
    public function test_has_parameters_with_required_values()
    {
        $this->assertEquals(
            array(
                Configure::P_LOG_FILE,
                Configure::P_LOG_LEVEL,
                Configure::P_LIMIT,
                Configure::P_FROM,
                Configure::P_TO,
                Configure::P_AUTHOR,
                Configure::P_CAMERAS,
                Configure::P_TOKENS_ADD,
                Configure::P_TOKENS_DROP,
            ),
            Configure::parametersRequiringValues()
        );
    }

    public function test_has_parameters_usable_multiple_times()
    {
        $this->assertEquals(
            array(
                Configure::P_FROM,
                Configure::P_CAMERAS,
                Configure::P_TOKENS_ADD,
                Configure::P_TOKENS_DROP,
            ),
            Configure::parametersUsableMultipleTimes()
        );
    }

    public function test_configure()
    {
        $configure = new Configure($this->argv());
        $this->assertArrayHasKey(0, $configure->positionedParameters);
        $this->assertEquals('/example/bin', $configure->positionedParameters[0]);
        $this->assertTrue($configure->help);
        $this->assertTrue($configure->debug);
        $this->assertTrue($configure->simulate);
        $this->assertEquals(42, $configure->limit);
        $this->assertFalse($configure->recursive);
        $this->assertEquals(array('/path/1', '/path/2', '/path/3'), $configure->from);
        $this->assertEquals('/path/dst', $configure->to);
        $this->assertFalse($configure->subDirectoriesStructure);
        $this->assertTrue($configure->copy);
        $this->assertFalse($configure->deleteDuplicates);
        $this->assertEquals('AUTHOR', $configure->author);
        $this->assertEquals(array('cam1', 'cam2', 'cam4'), $configure->cameras);
        $this->assertEquals(array('add1', 'add2'), $configure->tokensToAdd);
        $this->assertEquals(array('drop3', 'drop4'), $configure->tokensToDrop);
        $this->assertTrue($configure->tokensDropUnknown);
        $this->assertEquals('/tmp/example.log', $configure->logFile);
        $this->assertEquals(123, $configure->logLevel);
    }

    public function test_configure_failure_without_from()
    {
        $argv = $this->argv();
        unset($argv[7], $argv[8], $argv[9], $argv[10], $argv[11]);
        $this->setExpectedException('\\InvalidArgumentException', 'No source paths specified.', Configure::ERROR_NO_FROM);
        new Configure($argv);
    }

    public function test_configure_failure_without_to()
    {
        $argv = $this->argv();
        unset($argv[12], $argv[13]);
        $this->setExpectedException('\\InvalidArgumentException', 'No destination path specified.', Configure::ERROR_NO_TO);
        new Configure($argv);
    }

    private function argv()
    {
        return array(
            '/example/bin',
            '-h',
            '-E',
            '-s',
            '-l',
            '42',
            '-R',
            '-f',
            '/path/1',
            '-f',
            '/path/2',
            '/path/3',
            '-t',
            '/path/dst',
            '-D',
            '-c',
            '-Z',
            '-a',
            'AUTHOR',
            '-d',
            'cam1 cam2',
            '-d',
            'cam4',
            '-x',
            'add1 add2',
            '-y',
            'drop3 drop4',
            '-X',
            '-o',
            '/tmp/example.log',
            '-O',
            '123',
        );
    }

}
