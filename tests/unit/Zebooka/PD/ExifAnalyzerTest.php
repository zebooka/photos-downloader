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

    public function test_camera_detection()
    {
        foreach ($this->camerasExifsProperties() as $exifProperiesAndCamera) {
            list ($camera, $exifProperies) = $exifProperiesAndCamera;
            $exif = \Mockery::mock('\\Zebooka\\PD\\Exif');
            foreach ($exifProperies as $property => $value) {
                $exif->{$property} = $value;
            }
            $analyzer = new ExifAnalyzer($this->configure());
            list(, $detectedCamera) = $analyzer->extractDateTimeCamera($this->photoBunch(array($exif)));
            $this->assertEquals($camera, $detectedCamera);
        }
    }

    private function camerasExifsProperties()
    {
        return array(
            array('htc', array('Make' => 'HTC', 'Model' => 'Desire S')),
            array('htc', array('Model' => 'HTC Desire S')),
            array('htc', array('Model' => 'HTC Saga')),
            array('d700', array('Model' => 'NIKON D700')),
            array('d700a', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'a')),
            array('d700b', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'b')),
            array('d700c', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'c')),
            array('d700d', array('Model' => 'NIKON D700', 'CustomSettingsBank' => 'd')),
            array('lx5', array('InternalSerialNumber' => '(F17) 2010:08:25 no. 0366')),
            array('k10z', array('InternalSerialNumber' => '4123986')),
            array('k10g', array('InternalSerialNumber' => '8041881')),
            array('ds', array('InternalSerialNumber' => '6011443')),
        );
    }
}
