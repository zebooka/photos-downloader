<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ConfigureTest extends TestCase
{
    public function test_configure()
    {
        $this->markTestSkipped('Test argv parsing with Command configure.');
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
        $this->markTestSkipped('Test argv parsing with Command configure.');
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
        $this->markTestSkipped('Test argv parsing with Command configure.');
        $configure = new Configure(array(1 => '-z', '+06:00'), []);
        $this->assertEquals('+06:00', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '-0600'), []);
        $this->assertEquals('-0600', $configure->timezone);
        $configure = new Configure(array(1 => '-z', '0600'), []);
        $this->assertNull($configure->timezone);
        $configure = new Configure(array(1 => '-z', '+600'), []);
        $this->assertNull($configure->timezone);
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
