<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;
use Zebooka\Utils\Cli\Parameters;

class Configure
{
//    const PATHS_FROM_STDIN = '-';
    const KEEP_IN_PLACE = '-';
//
//    const P_HELP = 'h'; //
//    const P_VERBOSE_LEVEL = 'E'; // ?
//    const P_LOG_FILE = 'o'; //  ?
//    const P_LOG_LEVEL = 'O'; // ?
//    const P_SIMULATE = 's'; //
//    const P_SAVE_COMMANDS_FILE = 'S'; // ?
//    const P_LIMIT = 'l'; //
//    const P_NO_RECURSIVE = 'R'; //
//    const P_FROM = 'f'; //
//    const P_LIST_FILE = 'F'; //
//    const P_TO = 't'; //
//    const P_NO_SUBDIRS = 'D'; //
//    const P_SUBDIRS_FORMAT = 'k'; // ?
//    const P_COPY = 'c'; //
//    const P_NO_DELETE_DUPLICATES = 'Z'; //
//    const P_AUTHOR = 'a'; //
//    const P_CAMERAS = 'd'; // ?
//    const P_PREFER_EXIF_DT = 'T';
//    const P_TIMEZONE = 'z';
//    const P_TOKENS_ADD = 'x';
//    const P_TOKENS_DROP = 'y';
//    const P_TOKENS_DROP_UNKNOWN = 'Y';
//    const P_NO_COMPARE_EXIFS = 'B';
//    const P_REGEXP_EXIF_FILTER = 'i';
//    const P_REGEXP_EXIF_NEGATIVE_FILTER = 'I';
//    const P_REGEXP_FILENAME_FILTER = 'g';
//    const P_REGEXP_FILENAME_NEGATIVE_FILTER = 'G';
//    const P_PANORAMIC_RATIO = 'p';

//    public $help = false;
//    public $verboseLevel = 100;
//    public $logFile = null;
//    public $logLevel = 250;
//    public $simulate = false;
//    public $saveCommandsFile = null;
//    public $limit = 0;
//    public $recursive = true;
//    public $from = [];
//    public $listFile = null;
//    public $to = self::KEEP_IN_PLACE;
//    public $subDirectoriesStructure = true;
//    public $subDirectoriesFormat = '%Y/%m';
//    public $copy = false;
//    public $deleteDuplicates = true;
//    public $author = null;
//    public $cameras = [];
//    public $preferExifDateTime = false;
//    public $timezone = null;
//    public $tokensToAdd = [];
//    public $tokensToDrop = [];
//    public $tokensDropUnknown = false;
//    public $compareExifs = true;
//    public $regexpExifFilter = [];
//    public $regexpExifNegativeFilter = [];
//    public $regexpFilenameFilter = null;
//    public $regexpFilenameNegativeFilter = null;
//    public $panoramicRatio = 2.0;
//    public $executableName;

    private $prefixes = [];
    private $authors = [];
    private $cameras = [];
    private $tokens = [];

    public function __construct(array $prefixes, array $authors, array $cameras, array $tokens)
    {
//        $argv = $this->decodeArgv($argv);
//        $this->help = !empty($argv->{self::P_HELP});
//        $this->verboseLevel = (isset($argv->{self::P_VERBOSE_LEVEL}) ? intval($argv->{self::P_VERBOSE_LEVEL}) : $this->verboseLevel);
//        $this->logFile = (isset($argv->{self::P_LOG_FILE}) ? strval($argv->{self::P_LOG_FILE}) : $this->logFile);
//        $this->logLevel = (isset($argv->{self::P_LOG_LEVEL}) ? intval($argv->{self::P_LOG_LEVEL}) : $this->logLevel);
//        $this->simulate = !empty($argv->{self::P_SIMULATE});
//        $this->saveCommandsFile = (isset($argv->{self::P_SAVE_COMMANDS_FILE})
//            ? strval($argv->{self::P_SAVE_COMMANDS_FILE})
//            : sys_get_temp_dir() . '/photos-downloader-' . date('Ymd-His-') . substr(base64_encode(md5(time() . rand(0, 1000000000))), 0, 7) . '.log');
//        $this->limit = (isset($argv->{self::P_LIMIT}) ? intval($argv->{self::P_LIMIT}) : $this->limit);
//        $this->recursive = empty($argv->{self::P_NO_RECURSIVE});
//        $this->from = (isset($argv->{self::P_FROM}) ? $argv->{self::P_FROM} : $this->from);
//        $this->listFile = (isset($argv->{self::P_LIST_FILE}) ? $argv->{self::P_LIST_FILE} : $this->listFile);
//        $this->to = (isset($argv->{self::P_TO}) ? strval($argv->{self::P_TO}) : $this->to);
//        $this->subDirectoriesFormat = (isset($argv->{self::P_SUBDIRS_FORMAT}) ? strval($argv->{self::P_SUBDIRS_FORMAT}) : $this->subDirectoriesFormat);
//        $this->copy = !empty($argv->{self::P_COPY});
//        $this->deleteDuplicates = empty($argv->{self::P_NO_DELETE_DUPLICATES});
//        $this->author = (isset($argv->{self::P_AUTHOR}) ? strval($argv->{self::P_AUTHOR}) : $this->author);
//        $this->cameras = $this->splitSpaceSeparated(isset($argv->{self::P_CAMERAS}) ? $argv->{self::P_CAMERAS} : $this->cameras);
//        $this->preferExifDateTime = !empty($argv->{self::P_PREFER_EXIF_DT});
//        $this->timezone = (isset($argv->{self::P_TIMEZONE}) && preg_match('/^[+-][0-9]{2}:?[0-9]{2}$/', $argv->{self::P_TIMEZONE}) ? strval($argv->{self::P_TIMEZONE}) : $this->timezone);
//        $this->compareExifs = empty($argv->{self::P_NO_COMPARE_EXIFS});
//        $this->regexpExifFilter = (isset($argv->{self::P_REGEXP_EXIF_FILTER}) ? $this->splitKeyValues($argv->{self::P_REGEXP_EXIF_FILTER}) : $this->regexpExifFilter);
//        $this->regexpExifNegativeFilter = (isset($argv->{self::P_REGEXP_EXIF_NEGATIVE_FILTER}) ? $this->splitKeyValues($argv->{self::P_REGEXP_EXIF_NEGATIVE_FILTER}) : $this->regexpExifNegativeFilter);
//        $this->regexpFilenameFilter = (isset($argv->{self::P_REGEXP_FILENAME_FILTER}) ? strval($argv->{self::P_REGEXP_FILENAME_FILTER}) : $this->regexpFilenameFilter);
//        $this->regexpFilenameNegativeFilter = (isset($argv->{self::P_REGEXP_FILENAME_NEGATIVE_FILTER}) ? strval($argv->{self::P_REGEXP_FILENAME_NEGATIVE_FILTER}) : $this->regexpFilenameNegativeFilter);
//        $this->panoramicRatio = (isset($argv->{self::P_PANORAMIC_RATIO}) ? strval($argv->{self::P_PANORAMIC_RATIO}) : $this->panoramicRatio);
//        $this->from = array_unique(array_merge($this->from, array_slice($argv->positionedParameters(), 1)));
//        $positionedParameters = $argv->positionedParameters();
//        $this->executableName = (isset($positionedParameters[0]) ? $positionedParameters[0] : null);


        $this->prefixes = $prefixes;
        $this->authors = $authors;
        $this->cameras = $cameras;
        $this->tokens = $tokens;
    }

    public static function constructFromConfigFilename(string $filename): self
    {
        $config = json_decode(file_get_contents($filename), true);
        return new self(
            $config['prefixes'],
            $config['authors'],
            $config['cameras'],
            $config['tokens']
        );
    }

    public static function constructEmpty(): self
    {
        return new self([], [], [], []);
    }

    public static function author(InputInterface $input): ?string
    {
        return $input->getOption(Command::AUTHOR);
    }

    public static function preferExifDateTime(InputInterface $input): bool
    {
        return $input->getOption(Command::PREFER_EXIF_DT);
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

    /**
     * @return string[]
     */
    public function knownPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * @return string[]
     */
    public function knownAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @return string[]
     */
    public function knownCameras(): array
    {
        return array_keys($this->cameras);
    }

    /**
     * @return string[]
     */
    public function knownTokens(): array
    {
        return array_keys($this->tokens);
    }

    public function camerasConfigure()
    {
        return $this->cameras;
    }

    public function tokensConfigure()
    {
        return $this->tokens;
    }

    public static function tokensDropUnknown(InputInterface $input): bool
    {
        return (bool)$input->getOption(Command::TOKENS_DROP_UNKNOWN);
    }

    public static function tokensToAdd(InputInterface $input): array
    {
        return (array)$input->getOption(Command::TOKENS_ADD);
    }

    public static function tokensToDrop(InputInterface $input): array
    {
        return (array)$input->getOption(Command::TOKENS_DROP);
    }

    public static function isKeepInPlace(InputInterface $input): bool
    {
        return '-' === $input->getOption(Command::TO);
    }

    public static function to(InputInterface $input): string
    {
        return (string)$input->getOption(Command::TO);
    }

    public static function simulate(InputInterface $input): bool
    {
        return (bool)$input->getOption(Command::SIMULATE);
    }

    public static function subDirectoriesStructure(InputInterface $input): bool
    {
        return !$input->getOption(Command::NO_SUBDIRS);
    }

    public static function timezone(InputInterface $input): ?string
    {
        return $input->getOption(Command::TIMEZONE);
    }

    public static function panoramicRatio(InputInterface $input): float
    {
        return (float)$input->getOption(Command::PANORAMIC_RATIO);
    }

    public static function compareExifs(InputInterface $input): bool
    {
        return !$input->getOption(Command::NO_COMPARE_EXIFS);
    }

    public static function subDirectoriesFormat(InputInterface $input): string
    {
        return $input->getOption(Command::SUBDIRS_FORMAT);
    }

    public static function cameras(InputInterface $input): array
    {
        return (array)$input->getOption(Command::CAMERAS);
    }

    public static function regexpExifFilter(InputInterface $input): array
    {
        return (array)$input->getOption(Command::REGEXP_EXIF_FILTER);
    }

    public static function regexpExifNegativeFilter(InputInterface $input): array
    {
        return (array)$input->getOption(Command::REGEXP_EXIF_NEGATIVE_FILTER);
    }

    public static function regexpFilenameFilter(InputInterface $input): ?string
    {
        return $input->getOption(Command::REGEXP_FILENAME_FILTER);
    }

    public static function regexpFilenameNegativeFilter(InputInterface $input): ?string
    {
        return $input->getOption(Command::REGEXP_FILENAME_NEGATIVE_FILTER);
    }

    public static function deleteDuplicates(InputInterface $input): bool
    {
        return !$input->getOption(Command::NO_DELETE_DUPLICATES);
    }

    public static function copy(InputInterface $input): bool
    {
        return (bool)$input->getOption(Command::COPY);
    }

    public static function saveCommandsFile(InputInterface $input): ?string
    {
        return $input->getOption(Command::SAVE_COMMANDS_FILE);
    }


    public static function verboseLevel(InputInterface $input): string
    {
        return 250;
    }

    public static function logFile(InputInterface $input): ?string
    {
        return null;
    }

    public static function logLevel(InputInterface $input): string
    {
        return 250;
    }
}
