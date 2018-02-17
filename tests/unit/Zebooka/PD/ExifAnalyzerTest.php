<?php

namespace Zebooka\PD;

class ExifAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Configure
     */
    private function configure()
    {
        return \Mockery::mock('\\Zebooka\\PD\\Configure');
    }

    private function realConfigure()
    {
        return new \Zebooka\PD\Configure(
            array(1 => '-T'),
            json_decode(file_get_contents(__DIR__ . '/../../../../res/tokens.json'), true)
        );
    }

    /**
     * @param Exif[] $exifs
     * @return FileBunch
     */
    private function fileBunch(array $exifs)
    {
        return \Mockery::mock('\\Zebooka\\PD\\FileBunch')
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
        $datetimeProperties = array(
            'GPSDateTime',
            'DateTimeOriginal',
            'TrackCreateDate',
            'MediaCreateDate',
            'CreateDate',
            'CreationDate',
        );
        foreach ($datetimeProperties as $datetimeProperty) {
            /** @var Exif $exif */
            $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
            $exif->{$datetimeProperty} = $datetime;
            $analyzer = new ExifAnalyzer($this->realConfigure());
            list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
            $this->assertEquals(strtotime($datetime), $detectedDateTime);
        }
    }

    public function test_datetime_priority()
    {
        $analyzer = new ExifAnalyzer($this->realConfigure());

        // if difference is not large, we use DateTimeOriginal
        $replacedWithGps = false;
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
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
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif->GPSDateTime = $datetime = '2007-04-17 16:00:00';
        $exif->DateTimeOriginal = '2007-04-17 17:00:00';
        list($detectedDateTime, , , $replacedWithGps) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
        $this->assertTrue($replacedWithGps);
        $replacedWithGps = false;
        $detectedDateTime = $analyzer->extractDateTime($this->fileBunch(array($exif)), $replacedWithGps);
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
        $this->assertTrue($replacedWithGps);
    }

    public function test_cameras_detection()
    {
        foreach ($this->camerasExifsProperties() as $exifProperiesAndCamera) {
            list ($camera, $exifProperies) = $exifProperiesAndCamera;
            /** @var Exif $exif */
            $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
            foreach ($exifProperies as $property => $value) {
                $exif->{$property} = $value;
            }
            $analyzer = new ExifAnalyzer($this->realConfigure());
            list(, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
            $this->assertEquals($camera, $detectedCamera);
        }
    }

    public function test_tokens_detection()
    {
        foreach ($this->tokensExifsProperties() as $exifProperiesAndTokens) {
            list ($tokens, $exifProperies) = $exifProperiesAndTokens;
            /** @var Exif $exif */
            $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
            foreach ($exifProperies as $property => $value) {
                $exif->{$property} = $value;
            }
            $analyzer = new ExifAnalyzer($this->realConfigure());
            list(, , $detectedTokens) = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
            $this->assertEquals($tokens, $detectedTokens);
        }
    }

    public function test_detection_array()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $analyzer = new ExifAnalyzer($this->realConfigure());
        $detected = $analyzer->extractDateTimeCameraTokens($this->fileBunch(array($exif)));
        $this->assertInternalType('array', $detected);
        $this->assertCount(4, $detected);
    }

    public function test_failure_different_datetimes()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif1->DateTimeOriginal = '2007-04-17 16:00:00';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->realConfigure());
        $this->setExpectedException(
            '\\Zebooka\\PD\\ExifAnalyzerException',
            'Files have 2 unique date/time values.',
            ExifAnalyzerException::DIFFERENT_DATES
        );
        $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
    }

    public function test_failure_different_cameras()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif1->Model = 'HTC Desire S';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif2->Model = 'NIKON D700';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->realConfigure());
        $this->setExpectedException(
            '\\Zebooka\\PD\\ExifAnalyzerException',
            'Files have 2 unique detected cameras.',
            ExifAnalyzerException::DIFFERENT_CAMERAS
        );
        $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
    }

    public function test_no_failure_when_d700_and_d700x()
    {
        foreach (array('a', 'b', 'c', 'd') as $customSettingsBank) {
            /** @var Exif $exif1 */
            $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
            $exif1->Model = 'NIKON D700';
            /** @var Exif $exif2 */
            $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
            $exif2->Model = 'NIKON D700';
            $exif2->CustomSettingsBank = $customSettingsBank;
            $exifs = array($exif1, $exif2);
            $analyzer = new ExifAnalyzer($this->realConfigure());
            list (, $camera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
            $this->assertEquals('d700' . $customSettingsBank, $camera);
        }
    }

    public function test_no_failure_when_compareExifs_is_false()
    {
        $configure = $this->realConfigure();
        $configure->compareExifs = false;
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif1->DateTimeOriginal = '2007-04-17 16:00:00';
        $exif1->Model = 'HTC Desire S';
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exif2->Model = 'NIKON D700';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($configure);
        list ($detectedDateTime, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
        $this->assertEquals(strtotime('2007-04-17 16:00:00'), $detectedDateTime);
        $this->assertEquals('htc', $detectedCamera);
    }

    public function test_no_failure_when_detected_thing_is_null()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif2->DateTimeOriginal = '2007-04-21 23:00:00';
        $exif2->Model = 'NIKON D700';
        $exif2->Software = 'Snapseed';
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->realConfigure());
        list ($detectedDateTime, $detectedCamera, $detectedTokes) = $analyzer->extractDateTimeCameraTokens($this->fileBunch($exifs));
        $this->assertEquals(strtotime('2007-04-21 23:00:00'), $detectedDateTime);
        $this->assertEquals('d700', $detectedCamera);
        $this->assertEquals(array('snapseed'), $detectedTokes);
    }

    public function test_all_detected_cameras_are_known()
    {
        $configure = $this->realConfigure();
        $knownCameras = $configure->knownCameras();
        foreach ($this->camerasExifsProperties() as $exifProperiesAndCamera) {
            list ($camera, $exifProperies) = $exifProperiesAndCamera;
            $this->assertContains($camera, $knownCameras);
        }
    }

    private function camerasExifsProperties()
    {
        return array(
            array('htc', array('Make' => 'HTC', 'Model' => 'Desire S')),
            array('htc', array('Model' => 'HTC Desire S')),
            array('htc', array('Model' => 'HTC Saga')),
            array('5s', array('Make' => 'Apple', 'Model' => 'iPhone 5s')),
            array('5c', array('Make' => 'Apple', 'Model' => 'iPhone 5c')),
            array('4s', array('Make' => 'Apple', 'Model' => 'iPhone 4s')),
            array('mini', array('Make' => 'Apple', 'Model' => 'iPad mini')),
            array('d700', array('Model' => 'NIKON D700')),
            array('d700a', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'a')),
            array('d700b', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'b')),
            array('d700c', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'c')),
            array('d700d', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'd')),
            array('lx5', array('InternalSerialNumber' => '(F17) 2010:08:25 no. 0366')),
            array('k10z', array('InternalSerialNumber' => '4123986')),
            array('k10g', array('InternalSerialNumber' => '8041881')),
            array('ds', array('InternalSerialNumber' => '6011443')),
            array('k100d', array('InternalSerialNumber' => '6374615')),
            array('k100ds', array('InternalSerialNumber' => '6609148')),
            array('f5500', array('Make' => 'FUJIFILM', 'Model' => 'FinePix S5500')),
        );
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
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');

        $tokensConfig = array();
        $token = ExifAnalyzer::detectTokenIds($exif, $tokensConfig, false);
        $this->assertNull($token);
    }

    public function test_detectTokenIds_empty()
    {
        /** @var Exif $exif */
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');

        $tokensConfig = array();
        $tokens = ExifAnalyzer::detectTokenIds($exif, $tokensConfig, true);
        $this->assertEquals(array(), $tokens);
    }

    public function test_detectTokenIds_single()
    {
        /** @var Exif $exif1 */
        $exif1 = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif1->TestTag = 'unique-value';

        /** @var Exif $exif2 */
        $exif2 = \Mockery::mock('\\Zebooka\\PD\\Exif');
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
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
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
        $between = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $between->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2018-01-15'));
        $tokens = ExifAnalyzer::detectTokenIds($between, $tokensConfigure, true);
        $this->assertContains('between', $tokens);
        $this->assertContains('after', $tokens);
        $this->assertContains('before', $tokens);

        /** @var Exif $after */
        $after = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $after->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2018-02-02'));
        $tokens = ExifAnalyzer::detectTokenIds($after, $tokensConfigure, true);
        $this->assertNotContains('between', $tokens);
        $this->assertContains('after', $tokens);
        $this->assertNotContains('before', $tokens);

        /** @var Exif $before */
        $before = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $before->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2017-12-22'));
        $tokens = ExifAnalyzer::detectTokenIds($before, $tokensConfigure, true);
        $this->assertNotContains('between', $tokens);
        $this->assertNotContains('after', $tokens);
        $this->assertContains('before', $tokens);
    }
}
