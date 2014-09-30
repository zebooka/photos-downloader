<?php

namespace Zebooka\PD;

class ExifAnalyzer
{
    private $configure;

    public function __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    public function extractDateTimeCameraTokens(PhotoBunch $photoBunch)
    {
        $datetimes = $cameras = $tokens = array();
        try {
            $exifs = $photoBunch->exifs();
        } catch (\Exception $e) {
            throw new ExifAnalyzerException(
                'Unable to read one of Exifs.',
                ExifAnalyzerException::EXIF_EXCEPTION,
                $e
            );
        }
        foreach ($exifs as $extension => $exif) {
            if ($exif->DateTimeOriginal) {
                $datetimes[$extension] = strtotime($exif->DateTimeOriginal);
            }
            if (null !== ($camera = $this->detectCamera($exif))) {
                $cameras[$extension] = $camera;
            }
            $tokens = array_merge($tokens, $this->detectTokens($exif));
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
        $tokens = array_unique($tokens);

        return array(
            $datetimes ? reset($datetimes) : null,
            $cameras ? reset($cameras) : null,
            $tokens,
        );
    }

    private function detectCamera(Exif $exif)
    {
        if (('HTC' == $exif->Make && 'Desire S' == $exif->Model)
            || 'HTC Desire S' == $exif->Model || 'HTC Saga' == $exif->Model
        ) {
            return 'htc';
        } elseif ('Apple' == $exif->Make && 'iPhone 5s' == $exif->Model) {
            return '5s';
        } elseif ('Apple' == $exif->Make && 'iPhone 5c' == $exif->Model) {
            return '5c';
        } elseif ('Apple' == $exif->Make && 'iPhone 4s' == $exif->Model) {
            return '4s';
        } elseif ('Apple' == $exif->Make && 'iPad mini' == $exif->Model) {
            return 'mini';
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
        } elseif ('6374615' == $exif->InternalSerialNumber) {
            return 'k100d';
        } elseif ('6609148' == $exif->InternalSerialNumber) {
            return 'k100ds';
        } elseif ('FUJIFILM' == $exif->Make && 'FinePix S5500' == $exif->Model) {
            return 'f5500';
        } else {
            return null;
        }
    }

    private function detectTokens(Exif $exif)
    {
        $tags = array();
        if ('Instagram' == $exif->Software) {
            $tags[] = 'instagram';
        } elseif ('AutoStitch' == $exif->Software) {
            $tags[] = 'pano';
        } elseif (preg_match('/aviary/i', $exif->Software)) {
            $tags[] = 'aviary';
        } elseif (preg_match('/snapseed/i', $exif->Software)) {
            $tags[] = 'snapseed';
        }
        $min = min($exif->ImageWidth, $exif->ImageHeight);
        $max = max($exif->ImageWidth, $exif->ImageHeight);
        if ($min > 0 && $max / $min >= $this->configure->panoramicRatio) {
            $tags[] = 'pano';
        }
        return array_unique($tags);
    }
}
