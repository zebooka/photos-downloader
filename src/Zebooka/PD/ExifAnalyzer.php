<?php

namespace Zebooka\PD;

class ExifAnalyzer
{
    private $configure;

    public function __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    private function exifs(FileBunch $fileBunch)
    {
        try {
            return $fileBunch->exifs();
        } catch (\Exception $e) {
            throw new ExifAnalyzerException(
                'Unable to read one of Exifs.',
                ExifAnalyzerException::EXIF_EXCEPTION,
                $e
            );
        }
    }

    public function extractDateTime(FileBunch $fileBunch, &$replacedWithGps = false)
    {
        $datetimes = $gpsDatetimes = array();
        $datePropertiesNames = array(
            'DateTimeOriginal',
            'TrackCreateDate',
            'MediaCreateDate',
            'DateCreated',
            'CreateDate',
            'CreationDate',
            'ModifyDate',
            'GPSDateTime',
        );
        foreach ($this->exifs($fileBunch) as $extension => $exif) {
            foreach ($datePropertiesNames as $datePropertyName) {
                if ($exif->{$datePropertyName}) {
                    $datetimes[$extension] = strtotime($exif->{$datePropertyName});
                    break;
                }
            }
            if ($exif->GPSDateTime && $gpsDatetime = strtotime($exif->GPSDateTime)) {
                if ($this->configure->timezone) {
                    // correct GPS timestamp from specified TZ to local TZ
                    $gpsDatetime += time() - strtotime($this->configure->timezone);
                }
                $gpsDatetimes[$extension] = $gpsDatetime;
            }
        }
        $datetimes = array_unique($datetimes);
        if ($this->configure->compareExifs && count($datetimes) > 1) {
            throw new ExifAnalyzerException(
                'Files have ' . count($datetimes) . ' unique date/time values.',
                ExifAnalyzerException::DIFFERENT_DATES
            );
        }
        $datetime = ($datetimes ? reset($datetimes) : null);
        $gpsDatetimes = array_unique($gpsDatetimes);
        $gpsDatetime = ($gpsDatetimes ? reset($gpsDatetimes) : null);
        if ($this->configure->preferExifDateTime && $gpsDatetime && abs($datetime - $gpsDatetime) > 60) {
            // clock difference larger than 60 seconds
            $datetime = $gpsDatetime;
            $replacedWithGps = true;
        }
        return $datetime;
    }

    public function extractCamera(FileBunch $fileBunch)
    {
        $cameras = array();
        foreach ($this->exifs($fileBunch) as $extension => $exif) {
            if (null !== ($camera = $this->detectCamera($exif))) {
                $cameras[$extension] = $camera;
            }
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
                'Files have ' . count($cameras) . ' unique detected cameras.',
                ExifAnalyzerException::DIFFERENT_CAMERAS
            );
        }
        return ($cameras ? reset($cameras) : null);
    }

    public function extractTokens(FileBunch $fileBunch)
    {
        $tokens = array();
        foreach ($this->exifs($fileBunch) as $extension => $exif) {
            $tokens = array_merge($tokens, $this->detectTokens($exif));
        }
        return array_unique($tokens);
    }

    public function extractDateTimeCameraTokens(FileBunch $fileBunch)
    {
        $gpsDateTime = false;
        return array(
            $this->extractDateTime($fileBunch, $gpsDateTime),
            $this->extractCamera($fileBunch),
            $this->extractTokens($fileBunch),
            $gpsDateTime
        );
    }

    private function detectCamera(Exif $exif)
    {
        return self::detectTokenIds($exif, $this->configure->camerasConfigure(), false);
    }

    private function detectTokens(Exif $exif)
    {
        $tags = self::detectTokenIds($exif, $this->configure->tokensConfigure(), true);
        $min = min($exif->ImageWidth, $exif->ImageHeight);
        $max = max($exif->ImageWidth, $exif->ImageHeight);
        if ($min > 0 && $max / $min >= $this->configure->panoramicRatio) {
            $tags[] = 'pano';
        }
        return array_unique($tags);
    }

    /**
     * @param Exif $exif
     * @param array $tokensConfig
     * @param bool $allowMultiple
     * @return array|string|null
     */
    public static function detectTokenIds(Exif $exif, array $tokensConfig, $allowMultiple = false)
    {
        $detected = array();
        foreach ($tokensConfig as $tokenId => $conditions) {
            foreach ($conditions as $condition) {
                $before = isset($condition['before']) ? strtotime($condition['before']) : null;
                $after = isset($condition['after']) ? strtotime($condition['after']) : null;
                unset($condition['before'], $condition['after']);
                $matched = true;
                $extracts = array();
                $dateTimeUnix = strtotime($exif->DateTimeOriginal);
                if (null !== $before && $dateTimeUnix > $before) {
                    continue;
                }
                if (null !== $after && $dateTimeUnix < $after) {
                    continue;
                }
                foreach ($condition as $tag => $expression) {
                    if (preg_match('#^/.+/[a-z]*$#i', $expression)) {
                        if (preg_match($expression, $exif->{$tag}, $matches)) {
                            if (count($matches) > 1) {
                                $extracts = array_merge($extracts, array_slice($matches, 1));
                            }
                        } else {
                            $matched = false;
                            break;
                        }
                    } else {
                        if ($exif->{$tag} !== $expression) {
                            $matched = false;
                            break;
                        }
                    }
                }
                if ($matched) {
                    if (count($extracts) > 0) {
                        $detected = array_merge($detected, $extracts);
                    } else {
                        $detected[] = $tokenId;
                    }

                    if ($allowMultiple) {
                        break;
                    } else {
                        break 2;
                    }
                }
            }
        }

        if ($allowMultiple) {
            return $detected;
        } else {
            return (count($detected) > 0 ? reset($detected) : null);
        }
    }
}
