<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ExifTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/exif';
    }

    public function test_not_existing_file()
    {
        $filename = $this->resourceDirectory() . '/not-existing.jpg';
        $this->expectExceptionObject(new \InvalidArgumentException('File \'' . $filename . '\' not found or is not readable.'));
        new Exif($filename);
    }

    public function test_failing_exiftool()
    {
        $this->markTestSkipped('ExifTool since some version supports regular files and no longer issues error reading exif.');
        $filename = $this->resourceDirectory() . '/bad.jpg';
        $this->expectExceptionObject(new \RuntimeException('ExifTool failed with code #1.'));
        new Exif($filename);
    }

    public function test_exif_not_fails_on_file_with_array_data()
    {
        $filename = $this->resourceDirectory() . '/array_exif.jpg';
        $exif = new Exif($filename);
        $this->assertInstanceOf(Exif::class, $exif);
        $this->assertEquals('2020-11-04 14:48:00', $exif->CreateDate);
    }

    public function test_exif_read()
    {
        $filename = $this->resourceDirectory() . '/cubie.jpg';
        $exif = new Exif($filename);
        $this->assertObjectHasAttribute('SourceFile', $exif);
        $this->assertObjectHasAttribute('FileName', $exif);
        $this->assertObjectHasAttribute('FileModifyDate', $exif);
        $this->assertObjectHasAttribute('MIMEType', $exif);
        $this->assertObjectHasAttribute('Make', $exif);
        $this->assertObjectHasAttribute('Model', $exif);
        $this->assertObjectHasAttribute('Software', $exif);

        $this->assertObjectHasAttribute('DateTimeOriginal', $exif);
        $this->assertEquals('2012-12-21 16:16:37', $exif->DateTimeOriginal);

        $this->assertObjectHasAttribute('CreateDate', $exif);
        $this->assertEquals('2012-12-21 20:16:37 +04:00', $exif->CreateDate);

        $this->assertObjectNotHasAttribute('ModifyDate', $exif);
        $this->assertNull($exif->ModifyDate);
    }
}
