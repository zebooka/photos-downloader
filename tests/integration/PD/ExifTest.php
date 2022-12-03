<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class ExifTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../res/exif';
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
        $this->assertTrue(isset($exif->SourceFile));
        $this->assertTrue(isset($exif->FileName));
        $this->assertTrue(isset($exif->FileModifyDate));
        $this->assertTrue(isset($exif->MIMEType));
        $this->assertTrue(isset($exif->Make));
        $this->assertTrue(isset($exif->Model));
        $this->assertTrue(isset($exif->Software));

        $this->assertTrue(isset($exif->DateTimeOriginal));
        $this->assertEquals('2012-12-21 16:16:37', $exif->DateTimeOriginal);

        $this->assertTrue(isset($exif->CreateDate));
        $this->assertEquals('2012-12-21 20:16:37.555 +04:00', $exif->CreateDate);

        $this->assertFalse(isset($exif->ModifyDate));
        $this->assertNull($exif->ModifyDate);
    }
}
