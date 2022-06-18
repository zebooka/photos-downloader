<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ConfigureTest extends TestCase
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
                Configure::P_REGEXP_EXIF_FILTER,
                Configure::P_REGEXP_EXIF_NEGATIVE_FILTER,
                Configure::P_REGEXP_FILENAME_FILTER,
                Configure::P_REGEXP_FILENAME_NEGATIVE_FILTER,
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
                Configure::P_REGEXP_EXIF_FILTER,
                Configure::P_REGEXP_EXIF_NEGATIVE_FILTER,
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
        $this->assertEquals(['ColorSpace' => 'sRGB'], $configure->regexpExifFilter);
        $this->assertEquals(['XMPToolkit' => '/XMP/i'], $configure->regexpExifNegativeFilter);
        $this->assertEquals('/\\.jpe?g$/i', $configure->regexpFilenameFilter);
        $this->assertEquals('/\\.dng$/i', $configure->regexpFilenameNegativeFilter);
        $this->assertEquals(3.2, $configure->panoramicRatio);
    }

    public function test_empty_configure()
    {
        $configure = new Configure([], []);
        $this->assertNull($configure->executableName);
        $this->assertFalse($configure->help);
        $this->assertEquals(100, $configure->verboseLevel);
        $this->assertFalse($configure->simulate);
        $this->assertNotEmpty($configure->saveCommandsFile);
        $this->assertEquals(0, $configure->limit);
        $this->assertTrue($configure->recursive);
        $this->assertEquals([], $configure->from);
        $this->assertNull($configure->listFile);
        $this->assertEquals('-', $configure->to);
        $this->assertTrue($configure->subDirectoriesStructure);
        $this->assertEquals('%Y/%m', $configure->subDirectoriesFormat);
        $this->assertFalse($configure->preferExifDateTime);
        $this->assertNull($configure->timezone);
        $this->assertFalse($configure->copy);
        $this->assertTrue($configure->deleteDuplicates);
        $this->assertNull($configure->author);
        $this->assertEquals([], $configure->cameras);
        $this->assertEquals([], $configure->tokensToAdd);
        $this->assertEquals([], $configure->tokensToDrop);
        $this->assertFalse($configure->tokensDropUnknown);
        $this->assertTrue($configure->compareExifs);
        $this->assertNull($configure->logFile);
        $this->assertEquals(250, $configure->logLevel);
        $this->assertEquals([], $configure->knownAuthors());
        $this->assertEquals([], $configure->knownCameras());
        $this->assertEquals([], $configure->knownTokens());
        $this->assertEquals([], $configure->regexpExifFilter);
        $this->assertEquals([], $configure->regexpExifNegativeFilter);
        $this->assertNull($configure->regexpFilenameFilter);
        $this->assertNull($configure->regexpFilenameNegativeFilter);
        $this->assertEquals(2.0, $configure->panoramicRatio);
    }

    public function test_timezones()
    {
        $configure = new Configure(array(1 => '-z', '+06:00'), []);
        $this->assertEquals('+06:00', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '-0600'), []);
        $this->assertEquals('-0600', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '0600'), []);
        $this->assertNull($configure->timezone);
        $configure = new Configure(array(1 => '-z', '+600'), []);
        $this->assertNull($configure->timezone);
    }

    public function test_reassembling_configure()
    {
        $configure = new Configure($this->argv(), $this->knownData());
        $argv = array_merge([
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
        ],
        $this->implodeKeyValueParams(Configure::P_REGEXP_EXIF_FILTER, $configure->regexpExifFilter),
        $this->implodeKeyValueParams(Configure::P_REGEXP_EXIF_NEGATIVE_FILTER, $configure->regexpExifNegativeFilter),
        [
            '-' . Configure::P_REGEXP_FILENAME_FILTER,
            escapeshellarg($configure->regexpFilenameFilter),
            '-' . Configure::P_REGEXP_FILENAME_NEGATIVE_FILTER,
            escapeshellarg($configure->regexpFilenameNegativeFilter),
            '-' . Configure::P_PANORAMIC_RATIO,
            escapeshellarg($configure->panoramicRatio),
        ]);
        $this->assertEquals($argv, $configure->argv());
    }

    private function implodeKeyValueParams($param, array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = '-' . $param;
            $result[] = escapeshellarg($key . '=' . $value);
        }
        return $result;
    }

    private function knownData()
    {
        return array(
            'authors' => array(
                'unique-author-1',
                'unique-author-2',
            ),
            'cameras' => array(
                'unique-camera-1' => [],
                'unique-camera-2' => [],
                'unique-camera-3' => [],
            ),
            'tokens' => array(
                'unique-token-1' => [],
                'unique-token-2' => [],
                'unique-token-3' => [],
                'unique-token-4' => [],
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
            '-i',
            'ColorSpace=sRGB',
            '-I',
            'XMPToolkit=/XMP/i',
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
