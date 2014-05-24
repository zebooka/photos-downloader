<?php

namespace Zebooka\PD;

class ExifAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/exif-analyzer';
    }

    /**
     * @return Configure
     */
    private function configure()
    {
        return \Mockery::mock('\\Zebooka\\PD\\Configure');
    }

    /**
     * @param Exif[] $exifs
     * @return PhotoBunch
     */
    private function photoBunch(array $exifs)
    {
        return \Mockery::mock('\\Zebooka\\PD\\PhotoBunch')
            ->shouldReceive('exifs')
            ->withNoArgs()
            ->once()
            ->andReturn($exifs)
            ->getMock();
    }

    public function test_detection()
    {
        $datetime = '2007-04-17 16:00:00';
        foreach ($this->camerasExifsProperties() as $exifProperiesAndCamera) {
            list ($camera, $exifProperies) = $exifProperiesAndCamera;
            /** @var Exif $exif */
            $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
            $exif->DateTimeOriginal = $datetime;
            foreach ($exifProperies as $property => $value) {
                $exif->{$property} = $value;
            }
            $analyzer = new ExifAnalyzer($this->configure());
            list($detectedDateTime, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->photoBunch(array($exif)));
            $this->assertEquals(strtotime($datetime), $detectedDateTime);
            $this->assertEquals($camera, $detectedCamera);
        }
    }

    public function test_tokens_detection()
    {
        $datetime = '2007-04-17 16:00:00';
        $camera = '5s';
        $exifProperies = array('Make' => 'Apple', 'Model' => 'iPhone 5s', 'Software' => 'Instagram');
        /** @var Exif $exif */
        $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
        $exif->DateTimeOriginal = $datetime;
        foreach ($exifProperies as $property => $value) {
            $exif->{$property} = $value;
        }
        $analyzer = new ExifAnalyzer($this->configure());
        list($detectedDateTime, $detectedCamera, $detectedTokens) = $analyzer->extractDateTimeCameraTokens($this->photoBunch(array($exif)));
        $this->assertEquals(strtotime($datetime), $detectedDateTime);
        $this->assertEquals($camera, $detectedCamera);
        $this->assertContains('instagram', $detectedTokens);
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
        $exifs = array($exif1, $exif2);
        $analyzer = new ExifAnalyzer($this->configure());
        list ($detectedDateTime, $detectedCamera) = $analyzer->extractDateTimeCameraTokens($this->photoBunch($exifs));
        $this->assertEquals(strtotime('2007-04-21 23:00:00'), $detectedDateTime);
        $this->assertEquals('d700', $detectedCamera);
    }

    private function camerasExifsProperties()
    {
        return array(
            array('htc', array('Make' => 'HTC', 'Model' => 'Desire S')),
            array('htc', array('Model' => 'HTC Desire S')),
            array('htc', array('Model' => 'HTC Saga')),
            array('5s', array('Make' => 'Apple', 'Model' => 'iPhone 5s')),
            array('5c', array('Make' => 'Apple', 'Model' => 'iPhone 5c')),
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
}
