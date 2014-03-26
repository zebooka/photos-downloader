<?php

namespace Zebooka\PD;

class ConfigureTest extends \PHPUnit_Framework_TestCase
{
    public function test_has_parameters_with_required_values()
    {
        $this->assertEquals(
            array(
                Configure::P_VERBOSE_LEVEL,
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
        $knownData = array(
            'authors' => array('unique-author-1', 'unique-author-2'),
            'cameras' => array('unique-camera-1', 'unique-camera-2', 'unique-camera-3'),
            'tokens' => array('unique-token-1', 'unique-token-2', 'unique-token-3', 'unique-token-4'),
        );
        $configure = new Configure($this->argv(), $knownData);
        $this->assertEquals('/example/bin', $configure->executableName);
        $this->assertTrue($configure->help);
        $this->assertEquals(123, $configure->verboseLevel);
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
        $this->assertFalse($configure->compareExifs);
        $this->assertEquals('/tmp/example.log', $configure->logFile);
        $this->assertEquals(321, $configure->logLevel);
        $this->assertEquals($knownData['authors'], $configure->knownAuthors());
        $this->assertEquals($knownData['cameras'], $configure->knownCameras());
        $this->assertEquals($knownData['tokens'], $configure->knownTokens());
    }

    public function test_empty_configure()
    {
        $configure = new Configure(array(), array());
        $this->assertNull($configure->executableName);
        $this->assertFalse($configure->help);
        $this->assertEquals(100, $configure->verboseLevel);
        $this->assertFalse($configure->simulate);
        $this->assertEquals(0, $configure->limit);
        $this->assertTrue($configure->recursive);
        $this->assertEquals(array(), $configure->from);
        $this->assertEquals('-', $configure->to);
        $this->assertTrue($configure->subDirectoriesStructure);
        $this->assertFalse($configure->copy);
        $this->assertTrue($configure->deleteDuplicates);
        $this->assertNull($configure->author);
        $this->assertEquals(array(), $configure->cameras);
        $this->assertEquals(array(), $configure->tokensToAdd);
        $this->assertEquals(array(), $configure->tokensToDrop);
        $this->assertFalse($configure->tokensDropUnknown);
        $this->assertTrue($configure->compareExifs);
        $this->assertNull($configure->logFile);
        $this->assertEquals(250, $configure->logLevel);
        $this->assertEquals(array(), $configure->knownAuthors());
        $this->assertEquals(array(), $configure->knownCameras());
        $this->assertEquals(array(), $configure->knownTokens());
    }

    private function argv()
    {
        return array(
            '/example/bin',
            '-h',
            '-E',
            '123',
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
            '-Y',
            '-o',
            '/tmp/example.log',
            '-O',
            '321',
            '-B',
        );
    }

}
