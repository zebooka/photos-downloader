<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;
use Zebooka\Utils\Cli\Parameters;

/**
 * @property bool $help
 * @property bool $verboseLevel
 * @property null|string $logFile
 * @property int $logLevel
 * @property bool $simulate //
 * @property null|string $saveCommandsFile
 * @property int $limit
 * @property bool $recursive
 * @property array $from
 * @property string $to
 * @property bool $subDirectoriesStructure
 * @property string $subDirectoriesFormat
 * @property bool $copy
 * @property bool $deleteDuplicates
 * @property null|string $author
 * @property array $cameras
 * @property bool $preferExifDateTime
 * @property null|string $timezone
 * @property array $tokensToAdd
 * @property array $tokensToDrop
 * @property bool $tokensDropUnknown
 * @property bool $compareExifs
 * @property float $panoramicRatio
 * @property null|string $executableName
 */
class Configure
{
    const PATHS_FROM_STDIN = '-';
    const KEEP_IN_PLACE = '-';

    const P_HELP = 'h'; //
    const P_VERBOSE_LEVEL = 'E'; // ?
    const P_LOG_FILE = 'o'; //  ?
    const P_LOG_LEVEL = 'O'; // ?
    const P_SIMULATE = 's'; //
    const P_SAVE_COMMANDS_FILE = 'S'; // ?
    const P_LIMIT = 'l'; //
    const P_NO_RECURSIVE = 'R'; //
    const P_FROM = 'f'; //
    const P_LIST_FILE = 'F'; //
    const P_TO = 't'; //
    const P_NO_SUBDIRS = 'D'; //
    const P_SUBDIRS_FORMAT = 'k'; // ?
    const P_COPY = 'c'; //
    const P_NO_DELETE_DUPLICATES = 'Z'; //
    const P_AUTHOR = 'a'; //
    const P_CAMERAS = 'd'; // ?
    const P_PREFER_EXIF_DT = 'T';
    const P_TIMEZONE = 'z';
    const P_TOKENS_ADD = 'x';
    const P_TOKENS_DROP = 'y';
    const P_TOKENS_DROP_UNKNOWN = 'Y';
    const P_NO_COMPARE_EXIFS = 'B';
    const P_REGEXP_EXIF_FILTER = 'i';
    const P_REGEXP_EXIF_NEGATIVE_FILTER = 'I';
    const P_REGEXP_FILENAME_FILTER = 'g';
    const P_REGEXP_FILENAME_NEGATIVE_FILTER = 'G';
    const P_PANORAMIC_RATIO = 'p';

    public $help = false;
    public $verboseLevel = 100;
    public $logFile = null;
    public $logLevel = 250;
    public $simulate = false;
    public $saveCommandsFile = null;
    public $limit = 0;
    public $recursive = true;
    public $from = [];
    public $listFile = null;
    public $to = self::KEEP_IN_PLACE;
    public $subDirectoriesStructure = true;
    public $subDirectoriesFormat = '%Y/%m';
    public $copy = false;
    public $deleteDuplicates = true;
    public $author = null;
    public $cameras = [];
    public $preferExifDateTime = false;
    public $timezone = null;
    public $tokensToAdd = [];
    public $tokensToDrop = [];
    public $tokensDropUnknown = false;
    public $compareExifs = true;
    public $regexpExifFilter = [];
    public $regexpExifNegativeFilter = [];
    public $regexpFilenameFilter = null;
    public $regexpFilenameNegativeFilter = null;
    public $panoramicRatio = 2.0;
    public $executableName;

    private $knownAuthors = [];
    private $knownCameras = [];
    private $knownTokens = [];

    private $input;

    public function __construct(array $argv, InputInterface $input, array $knownLists)
    {
        $this->input = $input;

        $argv = $this->decodeArgv($argv);

        $this->help = !empty($argv->{self::P_HELP});
        $this->verboseLevel = (isset($argv->{self::P_VERBOSE_LEVEL}) ? intval($argv->{self::P_VERBOSE_LEVEL}) : $this->verboseLevel);
        $this->logFile = (isset($argv->{self::P_LOG_FILE}) ? strval($argv->{self::P_LOG_FILE}) : $this->logFile);
        $this->logLevel = (isset($argv->{self::P_LOG_LEVEL}) ? intval($argv->{self::P_LOG_LEVEL}) : $this->logLevel);
        $this->simulate = !empty($argv->{self::P_SIMULATE});
        $this->saveCommandsFile = (isset($argv->{self::P_SAVE_COMMANDS_FILE})
            ? strval($argv->{self::P_SAVE_COMMANDS_FILE})
            : sys_get_temp_dir() . '/photos-downloader-' . date('Ymd-His-') . substr(base64_encode(md5(time() . rand(0, 1000000000))), 0, 7) . '.log');
        $this->limit = (isset($argv->{self::P_LIMIT}) ? intval($argv->{self::P_LIMIT}) : $this->limit);
        $this->recursive = empty($argv->{self::P_NO_RECURSIVE});
        $this->from = (isset($argv->{self::P_FROM}) ? $argv->{self::P_FROM} : $this->from);
        $this->listFile = (isset($argv->{self::P_LIST_FILE}) ? $argv->{self::P_LIST_FILE} : $this->listFile);
        $this->to = (isset($argv->{self::P_TO}) ? strval($argv->{self::P_TO}) : $this->to);
        $this->subDirectoriesStructure = empty($argv->{self::P_NO_SUBDIRS});
        $this->subDirectoriesFormat = (isset($argv->{self::P_SUBDIRS_FORMAT}) ? strval($argv->{self::P_SUBDIRS_FORMAT}) : $this->subDirectoriesFormat);
        $this->copy = !empty($argv->{self::P_COPY});
        $this->deleteDuplicates = empty($argv->{self::P_NO_DELETE_DUPLICATES});
        $this->author = (isset($argv->{self::P_AUTHOR}) ? strval($argv->{self::P_AUTHOR}) : $this->author);
        $this->cameras = $this->splitSpaceSeparated(isset($argv->{self::P_CAMERAS}) ? $argv->{self::P_CAMERAS} : $this->cameras);
        $this->preferExifDateTime = !empty($argv->{self::P_PREFER_EXIF_DT});
        $this->timezone = (isset($argv->{self::P_TIMEZONE}) && preg_match('/^[+-][0-9]{2}:?[0-9]{2}$/', $argv->{self::P_TIMEZONE}) ? strval($argv->{self::P_TIMEZONE}) : $this->timezone);
        $this->tokensToAdd = $this->splitSpaceSeparated(isset($argv->{self::P_TOKENS_ADD}) ? $argv->{self::P_TOKENS_ADD} : $this->tokensToAdd);
        $this->tokensToDrop = $this->splitSpaceSeparated(isset($argv->{self::P_TOKENS_DROP}) ? $argv->{self::P_TOKENS_DROP} : $this->tokensToDrop);
        $this->tokensDropUnknown = !empty($argv->{self::P_TOKENS_DROP_UNKNOWN});
        $this->compareExifs = empty($argv->{self::P_NO_COMPARE_EXIFS});
        $this->regexpExifFilter = (isset($argv->{self::P_REGEXP_EXIF_FILTER}) ? $this->splitKeyValues($argv->{self::P_REGEXP_EXIF_FILTER}) : $this->regexpExifFilter);
        $this->regexpExifNegativeFilter = (isset($argv->{self::P_REGEXP_EXIF_NEGATIVE_FILTER}) ? $this->splitKeyValues($argv->{self::P_REGEXP_EXIF_NEGATIVE_FILTER}) : $this->regexpExifNegativeFilter);
        $this->regexpFilenameFilter = (isset($argv->{self::P_REGEXP_FILENAME_FILTER}) ? strval($argv->{self::P_REGEXP_FILENAME_FILTER}) : $this->regexpFilenameFilter);
        $this->regexpFilenameNegativeFilter = (isset($argv->{self::P_REGEXP_FILENAME_NEGATIVE_FILTER}) ? strval($argv->{self::P_REGEXP_FILENAME_NEGATIVE_FILTER}) : $this->regexpFilenameNegativeFilter);
        $this->panoramicRatio = (isset($argv->{self::P_PANORAMIC_RATIO}) ? strval($argv->{self::P_PANORAMIC_RATIO}) : $this->panoramicRatio);
        $this->from = array_unique(array_merge($this->from, array_slice($argv->positionedParameters(), 1)));
        $positionedParameters = $argv->positionedParameters();
        $this->executableName = (isset($positionedParameters[0]) ? $positionedParameters[0] : null);

        $this->knownAuthors = (isset($knownLists['authors']) && is_array($knownLists['authors']) ? $knownLists['authors'] : $this->knownAuthors);
        $this->knownCameras = (isset($knownLists['cameras']) && is_array($knownLists['cameras']) ? $knownLists['cameras'] : $this->knownCameras);
        $this->knownTokens = (isset($knownLists['tokens']) && is_array($knownLists['tokens']) ? $knownLists['tokens'] : $this->knownTokens);
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
        return method_exists($this, $property) ? $this->{$property}() : null;
    }

    public function preferExifDateTime(): bool
    {
        return $this->input->getOption(Command::PREFER_EXIF_DT);
    }

    private function splitSpaceSeparated(array $values)
    {
        $splitted = [];
        foreach ($values as $value) {
            $splitted = array_merge($splitted, preg_split('/[\\s,]+/', $value));
        }
        return array_unique($splitted);
    }

    private function splitKeyValues(array $values)
    {
        $result = [];
        foreach ($values as $value) {
            if (strpos($value, '=') !== false) {
                list ($key, $value) = explode('=', $value);
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /** @deprecated  */
    private function decodeArgv(array $argv)
    {
        return Parameters::createFromArgv(
            $argv,
            self::parametersRequiringValues(),
            self::parametersUsableMultipleTimes()
        );
    }

    /** @deprecated  */
    public function argv()
    {
        $parameters = new Parameters($this->encodeParameters());
        return $parameters->argv(
            self::parametersRequiringValues(),
            self::parametersUsableMultipleTimes()
        );
    }

    /**
     * @return string[]
     */
    public function knownAuthors()
    {
        return $this->knownAuthors;
    }

    /**
     * @return string[]
     */
    public function knownCameras()
    {
        return array_keys($this->knownCameras);
    }

    public function camerasConfigure()
    {
        return $this->knownCameras;
    }

    /**
     * @return string[]
     */
    public function knownTokens()
    {
        return array_keys($this->knownTokens);
    }

    public function tokensConfigure()
    {
        return $this->knownTokens;
    }

    public function isKeepInPlace()
    {
        return self::KEEP_IN_PLACE === $this->to;
    }

    /** @deprecated  */
    public static function parametersRequiringValues()
    {
        return array(
            self::P_VERBOSE_LEVEL,
            self::P_LOG_FILE,
            self::P_LOG_LEVEL,
            self::P_SAVE_COMMANDS_FILE,
            self::P_LIMIT,
            self::P_FROM,
            self::P_LIST_FILE,
            self::P_TO,
            self::P_SUBDIRS_FORMAT,
            self::P_AUTHOR,
            self::P_CAMERAS,
            self::P_TIMEZONE,
            self::P_TOKENS_ADD,
            self::P_TOKENS_DROP,
            self::P_REGEXP_EXIF_FILTER,
            self::P_REGEXP_EXIF_NEGATIVE_FILTER,
            self::P_REGEXP_FILENAME_FILTER,
            self::P_REGEXP_FILENAME_NEGATIVE_FILTER,
            self::P_PANORAMIC_RATIO,
        );
    }

    /** @deprecated  */
    public static function parametersUsableMultipleTimes()
    {
        return array(
            self::P_FROM,
            self::P_CAMERAS,
            self::P_TOKENS_ADD,
            self::P_TOKENS_DROP,
            self::P_REGEXP_EXIF_FILTER,
            self::P_REGEXP_EXIF_NEGATIVE_FILTER,
        );
    }
}
