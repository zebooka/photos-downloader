<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;

class ExifAnalyzerTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @return Configure
     */
    private function configure()
    {
        return \Mockery::mock(Configure::class)
            ->shouldReceive('camerasConfigure')
            ->andReturn([
                '5s' => [['Make' => 'Apple', 'Model' => 'iPhone 5s']],
                'htc' => [['Make' => 'HTC', 'Model' => 'Desire S'], ['Model' => 'HTC Desire S'], ['Model' => 'HTC Saga']],
                'd700a' => [['Model' => 'NIKON D700', 'CustomSettingsBank' => '/a/i']],
                'd700b' => [['Model' => 'NIKON D700', 'CustomSettingsBank' => '/b/i']],
                'd700c' => [['Model' => 'NIKON D700', 'CustomSettingsBank' => '/c/i']],
                'd700d' => [['Model' => 'NIKON D700', 'CustomSettingsBank' => '/d/i']],
                'd700' => [['Model' => 'NIKON D700']],
            ])
            ->getMock()
            ->shouldReceive('tokensConfigure')
            ->andReturn([
                'instagram' => [['Software' => 'Instagram']],
                'snapseed' => [[ 'Software' => '/snapseed/i' ]],
            ])
            ->getMock();
    }

    private function input($opts = [])
    {
        $defaults = [
            Command::PREFER_EXIF_DT => true,
            Command::TIMEZONE => null,
            Command::NO_COMPARE_EXIFS => false,
            Command::PANORAMIC_RATIO => 2,

        ];
        $mock = \Mockery::mock(InputInterface::class);
        foreach ($defaults as $option => $defaultValue) {
            $mock = $mock->shouldReceive('getOption')->with($option)->andReturn($opts[$option] ?? $defaultValue)->getMock();
        }
        return $mock;
    }

    /**
     * @param Exif[] $exifs
     * @return FileBunch
     */
    private function fileBunch(array $exifs)
    {
        return \Mockery::mock(FileBunch::class)
            ->shouldReceive('exifs')
            ->withNoArgs()
            ->atMost()
            ->times(3)
            ->andReturn($exifs)
            ->getMock();
    }

    public function test_datetime_detection()
    {
        $datetime = '2007-04-17 16:00:00';
        $datetimeProperties = [
            'GPSDateTime',
            'DateTimeOriginal',
            'TrackCreateDate',
            'MediaCreateDate',
            'CreateDate',
            'CreationDate',
        ];
        foreach ($datetimeProperties as $datetimeProperty) {
            /** @var Exif $exif */
            $exif = \Mockery::mock(Exif::class);
            $exif->{$datetimeProperty} = $datetime;
            $analyzer = new ExifAnalyzer($this->configure(), $this->input());
            list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
            $this->assertEquals(strtotime($datetime), $detectedDateTime);
        }
    }

    public function test_datetime_priority()
    {
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());

        // if difference is not large, we use DateTimeOriginal
        $replacedWithGps = false;
        $exif = \Mockery::mock(Exif::class);
        $exif->GPSDateTime = '2007-04-17 16:00:00';
        $exif->DateTimeOriginal = $datetime = '2007-04-17 16:00:10';
        list($detectedDateTime, , , $replacedWithGps) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
        $this->assertFalse($replacedWithGps);
        $replacedWithGps = false;
        $detectedDateTime = $analyzer->extractDateTime($this->fileBunch(array($exif)), $replacedWithGps);
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
        $this->assertFalse($replacedWithGps);

        // if difference is large, we use GPSDateTime
        $replacedWithGps = false;
        $exif = \Mockery::mock(Exif::class);
        $exif->GPSDateTime = $datetime = '2007-04-17 16:00:00';
        $exif->DateTimeOriginal = '2007-04-17 17:00:00';
        list($detectedDateTime, , , $replacedWithGps) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals($datetime, date('Y-m-d H:i:s', $detectedDateTime));
        $this->assertTrue($replacedWithGps);
        $replacedWithGps = false;
        $detectedDateTime = $analyzer->extractDateTime($this->fileBunch(array($exif)), $replacedWithGps);
        $this->assertEquals($datetime, date('Y-m-d H:i:s', $detectedDateTime));
        $this->assertTrue($replacedWithGps);
    }

    public function test_cameras_detection()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        $camera = '5s';
        $exifProperies = ['Make' => 'Apple', 'Model' => 'iPhone 5s'];
        foreach ($exifProperies as $property => $value) {
            $exif->{$property} = $value;
        }
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        list(, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals($camera, $detectedCamera);
    }

    public function test_tokens_detection()
    {
        $tokens = ['instagram'];
        $exifProperies =  ['Software' => 'Instagram'];
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        foreach ($exifProperies as $property => $value) {
            $exif->{$property} = $value;
        }
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        list(, , $detectedTokens) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals($tokens, $detectedTokens);
    }

    public function test_detection_array()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        $detected = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertIsArray($detected);
        $this->assertCount(4, $detected);
    }

    public function test_failure_different_datetimes()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        $exif1->DateTimeOriginal = '2007-04-17 16:00:00';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        $this->expectExceptionObject(
            new ExifAnalyzerException(
                'Files have 2 unique date/time values.',
                ExifAnalyzerException::DIFFERENT_DATES
            )
        );
        $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
    }

    public function test_success_1_sec_different_datetimes()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        $exif1->DateTimeOriginal = '2007-04-17 16:00:01';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->DateTimeOriginal = '2007-04-17 16:00:00';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        list($dt, $c, $t, $g) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
        $this->assertEquals(strtotime($exif2->DateTimeOriginal), $dt);
        $this->assertNotEquals(strtotime($exif1->DateTimeOriginal), $dt);
    }

    public function test_failure_different_cameras()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        $exif1->Model = 'HTC Desire S';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->Model = 'NIKON D700';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        $this->expectExceptionObject(
            new ExifAnalyzerException(
                'Files have 2 unique detected cameras.',
                ExifAnalyzerException::DIFFERENT_CAMERAS
            )
        );
        $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
    }

    public function test_no_failure_when_d700_and_d700x()
    {
        foreach (array('a', 'b', 'c', 'd') as $customSettingsBank) {
            /** @var Exif $exif1 */
            $exif1 = \Mockery::mock(Exif::class);
            $exif1->Model = 'NIKON D700';
            /** @var Exif $exif2 */
            $exif2 = \Mockery::mock(Exif::class);
            $exif2->Model = 'NIKON D700';
            $exif2->CustomSettingsBank = $customSettingsBank;
            $exifs = array($exif1, $exif2);
            $analyzer = new ExifAnalyzer($this->configure(), $this->input());
            list (, $camera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
            $this->assertEquals('d700' . $customSettingsBank, $camera);
        }
    }

    public function test_no_failure_when_compareExifs_is_false()
    {
        $input = $this->input([Command::NO_COMPARE_EXIFS => true]);
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        $exif1->DateTimeOriginal = '2007-04-17 16:00:00';
        $exif1->Model = 'HTC Desire S';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exif2->Model = 'NIKON D700';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure(), $input);
        list ($detectedDateTime, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
        $this->assertEquals(strtotime('2007-04-17 16:00:00'), $detectedDateTime);
        $this->assertEquals('htc', $detectedCamera);
    }

    public function test_no_failure_when_detected_thing_is_null()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exif2->Model = 'NIKON D700';
        $exif2->Software = 'Snapseed';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        list ($detectedDateTime, $detectedCamera, $detectedTokes) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
        $this->assertEquals(strtotime('2007-04-21 23:00:00'), $detectedDateTime);
        $this->assertEquals('d700', $detectedCamera);
        $this->assertEquals(array('snapseed'), $detectedTokes);
    }

    private function dataProviderCamerasExifs()
    {
        return [
            ['htc', ['Make' => 'HTC', 'Model' => 'Desire S']],
            ['htc', ['Model' => 'HTC Desire S']],
            ['htc', ['Model' => 'HTC Saga']],
            ['5s', ['Make' => 'Apple', 'Model' => 'iPhone 5s']],
            ['5c', ['Make' => 'Apple', 'Model' => 'iPhone 5c']],
            ['4s', ['Make' => 'Apple', 'Model' => 'iPhone 4s']],
            ['mini', ['Make' => 'Apple', 'Model' => 'iPad mini']],
            ['d700', ['Model' => 'NIKON D700']],
            ['d700a', ['Model' => 'NIKON D700', 'CustomSettingsBank' => 'a']],
            ['d700b', ['Model' => 'NIKON D700', 'CustomSettingsBank' => 'b']],
            ['d700c', ['Model' => 'NIKON D700', 'CustomSettingsBank' => 'c']],
            ['d700d', ['Model' => 'NIKON D700', 'CustomSettingsBank' => 'd']],
            ['lx5', ['InternalSerialNumber' => '(F17] 2010:08:25 no. 0366']],
            ['k10z', ['InternalSerialNumber' => '4123986']],
            ['k10g', ['InternalSerialNumber' => '8041881']],
            ['ds', ['InternalSerialNumber' => '6011443']],
            ['k100d', ['InternalSerialNumber' => '6374615']],
            ['k100ds', ['InternalSerialNumber' => '6609148']],
            ['f5500', ['Make' => 'FUJIFILM', 'Model' => 'FinePix S5500']],
        ];
    }

    private function tokensExifsProperties()
    {
        return array(
            array(array('instagram'), array('Software' => 'Instagram')),
            array(array('aviary'), array('Software' => 'Aviary')),
            array(array('snapseed'), array('Software' => 'Snapseed')),
            array(array('pano'), array('Software' => 'AutoStitch')),
            array(array('pano'), array('ImageWidth' => 1000, 'ImageHeight' => 500)),
            array(array(), array('ImageWidth' => 1000, 'ImageHeight' => 501)),
            array(array('vsco', 'abc123'), array('Software' => 'VSCOcam', 'Description' => 'Processed with VSCOcam with abc123 preset')),
            array(array('acd'), array('Software' => 'ACDSee')),
        );
    }

    public function test_detectTokenIds_null()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);

        $tokensConfig = array();
        $token = ExifAnalyzer::detectTokenIds($exif, $tokensConfig, false);
        $this->assertNull($token);
    }

    public function test_detectTokenIds_empty()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);

        $tokensConfig = array();
        $tokens = ExifAnalyzer::detectTokenIds($exif, $tokensConfig, true);
        $this->assertEquals(array(), $tokens);
    }

    public function test_detectTokenIds_single()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock(Exif::class);
        $exif1->TestTag = 'unique-value';

        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->AnotherTag = 'OTHER-Value 123';
        $exif2->SomeTag = 'some-value';

        $tokensConfig = array(
            'unique-token' => array(
                array('TestTag' => 'unique-value'),
                array('AnotherTag' => '/other-value/i'),
            ),
            'some-token' => array(
                array('SomeTag' => 'some-value'),
            ),
        );

        $token = ExifAnalyzer::detectTokenIds($exif1, $tokensConfig, false);
        $this->assertEquals('unique-token', $token);

        $token = ExifAnalyzer::detectTokenIds($exif2, $tokensConfig, false);
        $this->assertEquals('unique-token', $token);
    }

    public function test_detectTokenIds_multiple()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        $exif->TestTag = 'unique-value';
        $exif->AnotherTag = 'OTHER-Value 123';
        $exif->SomeTag = 'some-value';

        $tokensConfig = array(
            'unique-token' => array(
                array('TestTag' => 'unique-value'),
                array('AnotherTag' => '/other-value/i'),
            ),
            'some-token' => array(
                array('SomeTag' => '/SOME-value/'),
            ),
            123 => array(
                array('AnotherTag' => '/[0-9]+/'),
            ),
        );

        $tokens = ExifAnalyzer::detectTokenIds($exif, $tokensConfig, true);
        $this->assertEquals(array('unique-token', 123), $tokens);
    }


    public function test_before_between_after()
    {
        $tokensConfigure = array(
            'between' => array(
                array('after' => '2018-01-01', 'before' => '2018-02-01'),
            ),
            'after' => array(
                array('after' => '2018-01-01')
            ),
            'before' => array(
                array('before' => '2018-02-01')
            )
        );

        /** @var Exif $between */
        $between = \Mockery::mock(Exif::class);
        $between->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2018-01-15'));
        $tokens = ExifAnalyzer::detectTokenIds($between, $tokensConfigure, true);
        $this->assertContains('between', $tokens);
        $this->assertContains('after', $tokens);
        $this->assertContains('before', $tokens);

        /** @var Exif $after */
        $after = \Mockery::mock(Exif::class);
        $after->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2018-02-02'));
        $tokens = ExifAnalyzer::detectTokenIds($after, $tokensConfigure, true);
        $this->assertNotContains('between', $tokens);
        $this->assertContains('after', $tokens);
        $this->assertNotContains('before', $tokens);

        /** @var Exif $before */
        $before = \Mockery::mock(Exif::class);
        $before->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2017-12-22'));
        $tokens = ExifAnalyzer::detectTokenIds($before, $tokensConfigure, true);
        $this->assertNotContains('between', $tokens);
        $this->assertNotContains('after', $tokens);
        $this->assertContains('before', $tokens);
    }

    public function test_tz_dates_preferred()
    {
        $datetimeProperties = array(
            'DateTimeOriginal' => '2015-11-01 15:00:00',
            'TrackCreateDate' => '2015-11-01 15:00:00',
            'MediaCreateDate' => '2015-11-01 15:00:00',
            'CreateDate' => '2015-11-01 15:00:00',
            'CreationDate' => '2015-11-01 21:00:00 +06:00',  // only this value should be used
        );
        /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        foreach ($datetimeProperties as $exifTag => $exifValue) {
            $exif->{$exifTag} = $exifValue;
        }

        $analyzer = new ExifAnalyzer($this->configure(), $this->input());
        list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals(date('r', strtotime('2015-11-01 21:00:00 +06:00')), date('r', $detectedDateTime));
    }

    public function test_offset_datetime_detection()
    {
        $exif = new Exif(
            json_decode(file_get_contents(__DIR__ . '/../../res/exif/offset_exif1.json'), true),
            json_decode(file_get_contents(__DIR__ . '/../../res/exif/offset_exif2.json'), true)
        );
        $bunch = \Mockery::mock(FileBunch::class)
            ->shouldReceive('exifs')
            ->andReturn(['jpg' => $exif])
            ->getMock();

        $analyzer = new ExifAnalyzer($this->configure(), $this->input([Command::TIMEZONE => null]));
        list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($bunch);
        $this->assertEquals(date('r', strtotime('Tue, 23 May 2023 19:26:45')), date('r', $detectedDateTime));

        $analyzer = new ExifAnalyzer($this->configure(), $this->input([Command::TIMEZONE => '+0100']));
        list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($bunch);
        $this->assertEquals(date('r', strtotime('Tue, 23 May 2023 18:26:45')), date('r', $detectedDateTime));

        $analyzer = new ExifAnalyzer($this->configure(), $this->input([Command::TIMEZONE => '+0200']));
        list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($bunch);
        $this->assertEquals(date('r', strtotime('Tue, 23 May 2023 19:26:45')), date('r', $detectedDateTime));
    }
}
