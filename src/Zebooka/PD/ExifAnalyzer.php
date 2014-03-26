<?php

namespace Zebooka\PD;

class ExifAnalyzer
{
    private $configure;

    public function __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    public function extractDateTimeCamera(PhotoBunch $photoBunch)
    {
        $datetimes = $cameras = array();
        foreach ($photoBunch->exifs() as $extension => $exif) {
            if ($exif->DateTimeOriginal) {
                $datetimes[$extension] = strtotime($exif->DateTimeOriginal);
            }
            if (null !== ($camera = $this->detectCamera($exif))) {
                $cameras[$extension] = $camera;
            }
        }
        $datetimes = array_unique($datetimes);
        if ($this->configure->compareExifs && count($datetimes) > 1) {
            throw new ExifAnalyzerException(
                'Photos have ' . count($datetimes) . ' unique date/time values.',
                ExifAnalyzerException::DIFFERENT_DATES
            );
        }
        $cameras = array_unique($cameras);
        // remove d700, because it can be detected only from maker notes tag which is not available in processed jpg
        foreach (array('d700a', 'd700b', 'd700c', 'd700d') as $d700x) {
            if (in_array('d700', $cameras) && in_array($d700x, $cameras)) {
                $cameras = array_diff($cameras, array('d700'));
                break;
            }
        }
        if ($this->configure->compareExifs && count($cameras) > 1) {
            throw new ExifAnalyzerException(
                'Photos have ' . count($cameras) . ' unique detected cameras.',
                ExifAnalyzerException::DIFFERENT_CAMERAS
            );
        }

        return array(
            $datetimes ? reset($datetimes) : null,
            $cameras ? reset($cameras) : null
        );
    }

    private function detectCamera(Exif $exif)
    {
        if (('HTC' == $exif->Make && 'Desire S' == $exif->Model)
            || 'HTC Desire S' == $exif->Model || 'HTC Saga' == $exif->Model
        ) {
            return 'htc';
        } elseif ('NIKON D700' == $exif->Model) {
            return 'd700' . ($exif->CustomSettingsBank ? : '');
        } elseif ('(F17) 2010:08:25 no. 0366' == $exif->InternalSerialNumber) {
            return 'lx5';
        } elseif ('4123986' == $exif->InternalSerialNumber) {
            return 'k10z';
        } elseif ('8041881' == $exif->InternalSerialNumber) {
            return 'k10g';
        } elseif ('6011443' == $exif->InternalSerialNumber) {
            return 'ds';
        } else {
            return null;
        }
    }
}
