<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class InfraTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    private function exif($strtotime)
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        $exif->InternalSerialNumber = '(F17) 2010:08:25 no. 0366';
        $exif->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime($strtotime));
        return $exif;
    }

    private function realConfigure()
    {
        return new \Zebooka\PD\Configure(
            [],
            json_decode(file_get_contents(__DIR__ . '/../../../res/tokens.json'), true)
        );
    }

    public function test_after_151115_infra()
    {
        $this->markTestSkipped('Reimplement test as unit');
        $exifAnalyzer = new ExifAnalyzer($this->realConfigure());
        $tokens = $exifAnalyzer::detectTokenIds(
            $this->exif('2015-11-15'),
            $this->realConfigure()->tokensConfigure(),
            true
        );
        $this->assertContains('infra', $tokens);
    }

    public function test_before_151115_no_infra()
    {
        $this->markTestSkipped('Reimplement test as unit');
        $exifAnalyzer = new ExifAnalyzer($this->realConfigure());
        $tokens = $exifAnalyzer::detectTokenIds(
            $this->exif('2015-11-14'),
            $this->realConfigure()->tokensConfigure(),
            true
        );
        $this->assertNotContains('infra', $tokens);
    }
}
