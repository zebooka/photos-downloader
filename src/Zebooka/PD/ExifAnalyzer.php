<?php

namespace Zebooka\PD;

class ExifAnalyzer
{
    private $configure;

    public function __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    public function extractDateTimeCameraTokens(FileBunch $photoBunch)
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
        $datePropertiesNames = array(
            'DateTimeOriginal',
            'CreateDate',
            'CreationDate',
            'TrackCreateDate',
            'MediaCreateDate'
        );
        foreach ($exifs as $extension => $exif) {
            foreach ($datePropertiesNames as $datePropertyName) {
                if ($exif->{$datePropertyName}) {
                    $datetimes[$extension] = strtotime($exif->{$datePropertyName});
                    break;
                }
            }
            if (null !== ($camera = $this->detectCamera($exif))) {
                $cameras[$extension] = $camera;
            }
            $tokens = array_merge($tokens, $this->detectTokens($exif));
        }
        $datetimes = array_unique($datetimes);
        if ($this->configure->compareExifs && count($datetimes) > 1) {
            throw new ExifAnalyzerException(
                'Files have ' . count($datetimes) . ' unique date/time values.',
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
                'Files have ' . count($cameras) . ' unique detected cameras.',
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
                $matched = true;
                $extracts = array();
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
