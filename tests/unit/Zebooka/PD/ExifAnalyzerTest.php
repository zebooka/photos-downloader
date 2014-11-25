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
            array(),
            json_decode(file_get_contents(__DIR__ . '/../../../../res/tokens.json'), true)
        );
    }

    /**
     * @param Exif[] $exifs
     * @return FileBunch
     */
    private function photoBunch(array $exifs)
    {
        return \Mockery::mock('\\Zebooka\\PD\\FileBunch')
            ->shouldReceive('exifs')
            ->withNoArgs()
            ->once()
            ->andReturn($exifs)
            ->getMock();
    }

    public function test_datetime_detection()
    {
        $datetime = '2007-04-17 16:00:00';
        /** @var Exif $exif */
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif->DateTimeOriginal = $datetime;
        $analyzer = new ExifAnalyzer($this->configure());
        list($detectedDateTime) = $analyzer->extractDateTimeCameraTokens($this->photoBunch(array($exif)));
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
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
            $analyzer = new ExifAnalyzer($this->configure());
            list(, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->photoBunch(array($exif)));
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
            $analyzer = new ExifAnalyzer($this->configure());
            list(, , $detectedTokens) = $analyzer->extractDateTimeCameraTokens($this->photoBunch(array($exif)));
            $this->assertEquals($tokens, $detectedTokens);
        }
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
        $analyzer = new ExifAnalyzer($this->configure());
        $this->setExpectedException(
            '\\Zebooka\\PD\\ExifAnalyzerException',
            'Photos have 2 unique date/time values.',
            ExifAnalyzerException::DIFFERENT_DATES
        );
        $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
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
        $analyzer = new ExifAnalyzer($this->configure());
        $this->setExpectedException(
            '\\Zebooka\\PD\\ExifAnalyzerException',
            'Photos have 2 unique detected cameras.',
            ExifAnalyzerException::DIFFERENT_CAMERAS
        );
        $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
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
            $analyzer = new ExifAnalyzer($this->configure());
            list (, $camera) = $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
            $this->assertEquals('d700' . $customSettingsBank, $camera);
        }
    }

    public function test_no_failure_when_compareExifs_is_false()
    {
        $configure = $this->configure();
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
        list ($detectedDateTime, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
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
        $analyzer = new ExifAnalyzer($this->configure());
        list ($detectedDateTime, $detectedCamera, $detectedTokes) = $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
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
        );
    }
}
