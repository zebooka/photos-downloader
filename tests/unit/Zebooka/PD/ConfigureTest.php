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
                Configure::P_SAVE_COMMANDS_FILE,
                Configure::P_LIMIT,
                Configure::P_FROM,
                Configure::P_LIST_FILE,
                Configure::P_TO,
                Configure::P_SUBDIRS_FORMAT,
                Configure::P_AUTHOR,
                Configure::P_CAMERAS,
                Configure::P_TIMEZONE,
                Configure::P_TOKENS_ADD,
                Configure::P_TOKENS_DROP,
                Configure::P_REGEXP_FILTER,
                Configure::P_REGEXP_NEGATIVE_FILTER,
                Configure::P_PANORAMIC_RATIO,
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
        $knownData = $this->knownData();
        $configure = new Configure($this->argv(), $knownData);
        $this->assertEquals('/example/bin', $configure->executableName);
        $this->assertTrue($configure->help);
        $this->assertEquals(123, $configure->verboseLevel);
        $this->assertTrue($configure->simulate);
        $this->assertEquals('/tmp/pd.log', $configure->saveCommandsFile);
        $this->assertEquals(42, $configure->limit);
        $this->assertFalse($configure->recursive);
        $this->assertEquals(array('/path/1', '/path/2', '/path/3'), $configure->from);
        $this->assertEquals('/path/list.txt', $configure->listFile);
        $this->assertEquals('/path/dst', $configure->to);
        $this->assertFalse($configure->subDirectoriesStructure);
        $this->assertEquals('%Y/%y%m00', $configure->subDirectoriesFormat);
        $this->assertTrue($configure->preferExifDateTime);
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
        $this->assertEquals(array_keys($knownData['cameras']), $configure->knownCameras());
        $this->assertEquals(array_keys($knownData['tokens']), $configure->knownTokens());
        $this->assertEquals('/\\.jpe?g$/i', $configure->regexpFilter);
        $this->assertEquals('/\\.dng$/i', $configure->regexpNegativeFilter);
        $this->assertEquals(3.2, $configure->panoramicRatio);
    }

    public function test_empty_configure()
    {
        $configure = new Configure(array(), array());
        $this->assertNull($configure->executableName);
        $this->assertFalse($configure->help);
        $this->assertEquals(100, $configure->verboseLevel);
        $this->assertFalse($configure->simulate);
        $this->assertNull($configure->saveCommandsFile);
        $this->assertEquals(0, $configure->limit);
        $this->assertTrue($configure->recursive);
        $this->assertEquals(array(), $configure->from);
        $this->assertNull($configure->listFile);
        $this->assertEquals('-', $configure->to);
        $this->assertTrue($configure->subDirectoriesStructure);
        $this->assertEquals('%Y/%m', $configure->subDirectoriesFormat);
        $this->assertFalse($configure->preferExifDateTime);
        $this->assertNull($configure->timezone);
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
        $this->assertNull($configure->regexpFilter);
        $this->assertNull($configure->regexpNegativeFilter);
        $this->assertEquals(2.0, $configure->panoramicRatio);
    }

    public function test_timezones()
    {
        $configure = new Configure(array(1 => '-z', '+06:00'), array());
        $this->assertEquals('+06:00', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '-0600'), array());
        $this->assertEquals('-0600', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '0600'), array());
        $this->assertNull($configure->timezone);
        $configure = new Configure(array(1 => '-z', '+600'), array());
        $this->assertNull($configure->timezone);
    }

    public function test_reassembling_configure()
    {
        $configure = new Configure($this->argv(), $this->knownData());
        $argv = array(
            escapeshellarg($configure->executableName),
            '-' . Configure::P_HELP,
            '-' . Configure::P_VERBOSE_LEVEL,
            escapeshellarg($configure->verboseLevel),
            '-' . Configure::P_LOG_FILE,
            escapeshellarg($configure->logFile),
            '-' . Configure::P_LOG_LEVEL,
            escapeshellarg($configure->logLevel),
            '-' . Configure::P_SIMULATE,
            '-' . Configure::P_SAVE_COMMANDS_FILE,
            escapeshellarg($configure->saveCommandsFile),
            '-' . Configure::P_LIMIT,
            escapeshellarg($configure->limit),
            '-' . Configure::P_NO_RECURSIVE,
            '-' . Configure::P_FROM,
            escapeshellarg($configure->from[0]),
            '-' . Configure::P_FROM,
            escapeshellarg($configure->from[1]),
            '-' . Configure::P_FROM,
            escapeshellarg($configure->from[2]),
            '-' . Configure::P_LIST_FILE,
            escapeshellarg($configure->listFile),
            '-' . Configure::P_TO,
            escapeshellarg($configure->to),
            '-' . Configure::P_NO_SUBDIRS,
            '-' . Configure::P_SUBDIRS_FORMAT,
            escapeshellarg($configure->subDirectoriesFormat),
            '-' . Configure::P_COPY,
            '-' . Configure::P_NO_DELETE_DUPLICATES,
            '-' . Configure::P_AUTHOR,
            escapeshellarg($configure->author),
            '-' . Configure::P_CAMERAS,
            escapeshellarg($configure->cameras[0]),
            '-' . Configure::P_CAMERAS,
            escapeshellarg($configure->cameras[1]),
            '-' . Configure::P_CAMERAS,
            escapeshellarg($configure->cameras[2]),
            '-' . Configure::P_PREFER_EXIF_DT,
            '-' . Configure::P_TIMEZONE,
            escapeshellarg($configure->timezone),
            '-' . Configure::P_TOKENS_ADD,
            escapeshellarg($configure->tokensToAdd[0]),
            '-' . Configure::P_TOKENS_ADD,
            escapeshellarg($configure->tokensToAdd[1]),
            '-' . Configure::P_TOKENS_DROP,
            escapeshellarg($configure->tokensToDrop[0]),
            '-' . Configure::P_TOKENS_DROP,
            escapeshellarg($configure->tokensToDrop[1]),
            '-' . Configure::P_TOKENS_DROP_UNKNOWN,
            '-' . Configure::P_NO_COMPARE_EXIFS,
            '-' . Configure::P_REGEXP_FILTER,
            escapeshellarg($configure->regexpFilter),
            '-' . Configure::P_REGEXP_NEGATIVE_FILTER,
            escapeshellarg($configure->regexpNegativeFilter),
            '-' . Configure::P_PANORAMIC_RATIO,
            escapeshellarg($configure->panoramicRatio),
        );
        $this->assertEquals($argv, $configure->argv());
    }

    private function knownData()
    {
        return array(
            'authors' => array(
                'unique-author-1',
                'unique-author-2',
            ),
            'cameras' => array(
                'unique-camera-1' => array(),
                'unique-camera-2' => array(),
                'unique-camera-3' => array(),
            ),
            'tokens' => array(
                'unique-token-1' => array(),
                'unique-token-2' => array(),
                'unique-token-3' => array(),
                'unique-token-4' => array(),
            ),
        );
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
            '-F',
            '/path/list.txt',
            '-t',
            '/path/dst',
            '-D',
            '-k',
            '%Y/%y%m00',
            '-c',
            '-Z',
            '-a',
            'AUTHOR',
            '-d',
            'cam1 cam2',
            '-d',
            'cam4',
            '-T',
            '-z',
            '+06:00',
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
            '-g',
            '/\\.jpe?g$/i',
            '-G',
            '/\\.dng$/i',
            '-p',
            '3.2',
            '-S',
            '/tmp/pd.log',
        );
    }

}
